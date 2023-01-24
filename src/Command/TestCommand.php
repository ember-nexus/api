<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Service\AccessChecker;
use App\Service\ElementManager;
use App\Type\NodeElement;
use App\Type\RelationElement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;
use Syndesi\MongoEntityManager\Type\EntityManager as MongoEntityManager;

#[AsCommand(name: 'app:test')]
class TestCommand extends Command
{
    public function __construct(
        private ElementManager $elementManager,
        private AccessChecker $accessChecker,
        private UserRepository $userRepository,
        private ElasticEntityManager $elasticEntityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
//        $this->cypherEntityManager->run('MATCH (n) DETACH DELETE n;');
//        $this->mongoEntityManager->getClient()->dropDatabase('tion');

        $backupPath = __DIR__.'/../../var/backup/';

//        $elasticaClient = $this->elasticEntityManager->getClient();
//        $elasticaClient->indices()->delete(['index' => '_all']);

        $nodePaths = scandir($backupPath.'/node/');
        foreach ($nodePaths as $nodePath) {
            if ('.' === $nodePath || '..' === $nodePath) {
                continue;
            }
            $data = file_get_contents($backupPath.'/node/'.$nodePath);
            $data = json_decode($data, true);

            $element = (new NodeElement())
                ->setLabel($data['type'])
                ->setIdentifier(Uuid::fromString($data['id']))
                ->addProperties($data['data']);
            $this->elementManager->merge($element);
        }
        $this->elementManager->flush();

        $relationPaths = scandir($backupPath.'/relation/');
        foreach ($relationPaths as $relationPath) {
            if ('.' === $relationPath || '..' === $relationPath) {
                continue;
            }
            $data = file_get_contents($backupPath.'/relation/'.$relationPath);
            $data = json_decode($data, true);

            $element = (new RelationElement())
                ->setType($data['type'])
                ->setIdentifier(Uuid::fromString($data['id']))
                ->setStartNode(Uuid::fromString($data['start']))
                ->setEndNode(Uuid::fromString($data['end']))
                ->addProperties($data['data']);
            $this->elementManager->merge($element);
        }
        $this->elementManager->flush();

//        $res = $this->em->getClient()->runStatement(Statement::create("MATCH (user:User {id: '6ce3006b-6b7f-4770-8075-d2bf91804d14'}) RETURN user"));
//        $node = Neo4jClientHelper::getNodeFromLaudisNode($res->first()->get('user'));
//        echo("---\n");
//        print_r($node);
//        echo("---\n");
//        print_r($userA);
//        echo("---\n");
//
//        $res = $this->em->getClient()->runStatement(Statement::create(
//            "MATCH (user:User {id: '4ecb295f-693e-423b-9c7e-1994158d1da8'})\n".
//            "MATCH (data:Data {id: 'd95b284a-734f-4a87-934f-6e901a51322b'})\n".
//            "MATCH (action:Action {id: '033cef01-eeb6-4503-9903-66138aee2666'})\n".
//            "MATCH p1=(user)-[:PART_OF_GROUP|HAS_RULE*]->(rule:Rule)-[:HAS_ACTION]->(action)\n".
//            "MATCH p2=(rule)-[:APPLIES]->()-[:OWNS*0..]->(data)\n".
//            "RETURN rule"
//        ));
//        /**
//         * @var $p1 \Laudis\Neo4j\Types\Path
//         */
//        $p1 = $res->first()->get('p1');
//        $nodes = [];
//        foreach ($p1->getNodes()->toArray() as $node) {
//            $nodes[] = Neo4jClientHelper::getNodeFromLaudisNode($node);
//        }
//        print_r($nodes);
//        $rule = Neo4jClientHelper::getNodeFromLaudisNode($res->first()->get('rule'));
//        print_r($rule);

        $res = $this->accessChecker->checkAccessToNode(
            UuidV4::fromString('4ecb295f-693e-423b-9c7e-1994158d1da8'),
            UuidV4::fromString('033cef01-eeb6-4503-9903-66138aee2666'),
//            UuidV4::fromString('e65c2224-3949-455a-b08a-cf150348982a')
            UuidV4::fromString('13ba4480-1310-44b1-9ebd-b0236069d250')
        );
        if (true === $res) {
            $output->writeln('Access granted');
        } else {
            $output->writeln('Access denied');
        }

        $output->writeln('hello world :D');

        return Command::SUCCESS;
    }
}
