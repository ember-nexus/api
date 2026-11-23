<?php

declare(strict_types=1);

namespace App\Command\Cron;

use App\Service\CronExecutionGateService;
use App\Service\CronTimeBudgetService;
use App\Service\DeletionService;
use App\Service\ElementManager;
use App\Style\EmberNexusStyle;
use App\Type\TokenStateType;
use App\Type\Uuid;
use DateInterval;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Databags\Statement;
use Safe\DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

/**
 * Housekeeping for tokens. Expired tokens are already rejected during authentication, this command only keeps the
 * stored state truthful: active tokens whose expiration date has passed become `EXPIRED`, and tokens which expired
 * longer ago than `token.tokenDeleteExpiredTokensAutomaticallyInSeconds` are deleted (disabled with `false`).
 *
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'cron:expire-tokens', description: 'Marks expired tokens as expired and deletes tokens which expired long ago.')]
class ExpireTokensCommand extends Command
{
    private const int PAGE_SIZE = 100;

    private EmberNexusStyle $io;

    public function __construct(
        private CronExecutionGateService $cronExecutionGateService,
        private CronTimeBudgetService $cronTimeBudgetService,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private CypherEntityManager $cypherEntityManager,
        private ElementManager $elementManager,
        private DeletionService $deletionService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);
        $this->io->title('Cron');

        if ($this->cronExecutionGateService->shouldSkipExecution()) {
            $this->io->finalMessage('Cron is disabled; this command terminates early.');

            return Command::SUCCESS;
        }

        $deadline = $this->cronTimeBudgetService->getDeadline();

        $expired = $this->markExpiredTokens($deadline);
        $deleted = $this->deleteOldTokens($deadline);

        $this->io->finalMessage(sprintf('Marked %d token(s) as expired, deleted %d token(s).', $expired, $deleted));

        return Command::SUCCESS;
    }

    private function markExpiredTokens(?int $deadline): int
    {
        $total = 0;
        do {
            $result = $this->cypherEntityManager->getClient()->runStatement(new Statement(
                'MATCH (t:Token {state: $active}) WHERE t.expirationDate < datetime() '.
                'WITH t LIMIT $pageSize SET t.state = $expired RETURN count(t) AS count',
                [
                    'active' => TokenStateType::ACTIVE->value,
                    'expired' => TokenStateType::EXPIRED->value,
                    'pageSize' => self::PAGE_SIZE,
                ]
            ));
            $count = $result->first()->get('count');
            $count = is_int($count) ? $count : 0;
            $total += $count;
        } while ($count >= self::PAGE_SIZE && (null === $deadline || time() < $deadline));

        return $total;
    }

    private function deleteOldTokens(?int $deadline): int
    {
        $afterSeconds = $this->emberNexusConfiguration->getTokenDeleteExpiredTokensAutomaticallyInSeconds();
        if (false === $afterSeconds) {
            return 0;
        }
        $threshold = (new DateTime())->sub(new DateInterval(sprintf('PT%sS', $afterSeconds)));

        $total = 0;
        // keyset pagination: tokens which can't be loaded or deleted stay in the database, so the next page has to
        // start after the last token of this page instead of fetching the same page again
        $lastId = '';
        do {
            $result = $this->cypherEntityManager->getClient()->runStatement(new Statement(
                'MATCH (t:Token) WHERE t.expirationDate < $threshold AND t.id > $lastId '.
                'RETURN t.id AS id ORDER BY t.id LIMIT $pageSize',
                ['threshold' => $threshold, 'lastId' => $lastId, 'pageSize' => self::PAGE_SIZE]
            ));
            $count = 0;
            foreach ($result as $row) {
                $id = $row->get('id');
                if (!is_string($id)) {
                    continue;
                }
                ++$count;
                $lastId = $id;
                $token = $this->elementManager->getElement(Uuid::fromString($id));
                if (null === $token) {
                    continue;
                }
                $this->deletionService->delete($token);
                ++$total;
            }
            $this->elementManager->flush();
        } while ($count >= self::PAGE_SIZE && (null === $deadline || time() < $deadline));

        return $total;
    }
}
