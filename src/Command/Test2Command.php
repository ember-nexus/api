<?php

namespace App\Command;

use App\Service\ElementManager;
use App\Service\RawToElementService;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test2')]
class Test2Command extends Command
{
    public function __construct(
        private RawToElementService $rawToElementService,
        private ElementManager $elementManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('hello world :D');

        $data = [
            'id' => 'fdc8cfa2-8467-4368-8746-10def3c8297b',
            'type' => 'SomeNode',
            'name' => 'Test :D',
            'shortString' => 'hello world :D',
            'integer' => 1234,
            'float' => 1.234,
            'longString' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut '.
                'labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores '.
                'et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem '.
                'ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et '.
                'dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. '.
                'Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit '.
                'amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna '.
                'aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita '.
                "kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.\n".
                'Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum '.
                'dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit '.
                'praesent luptatu',
            'array' => [1, 2, 3, 4],
            'setting' => [
                'frontend' => [
                    'theme' => 'light',
                    'heighContrast' => 'false',
                    'units' => [
                        'temperature' => 'k',
                        'distance' => 'km',
                        'weight' => 'kg',
                    ],
                ],
            ],
        ];

        $element = $this->rawToElementService->rawToElement($data);
        $this->elementManager->merge($element);
        $this->elementManager->flush();

        $node = $this->elementManager->getNode(UuidV4::fromString('78f61245-8cfd-4039-ac7e-61407fa7e969'));
        print_r($node);

        return Command::SUCCESS;
    }
}
