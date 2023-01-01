<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Service\AccessChecker;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Syndesi\CypherDataStructures\Type\Node;
use Syndesi\CypherDataStructures\Type\Relation;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\MongoEntityManager\Type\EntityManager as MongoEntityManager;

#[AsCommand(name: 'app:test')]
class TestCommand extends Command
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private MongoEntityManager $mongoEntityManager,
        private AccessChecker $accessChecker,
        private UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $elements = [];
        $elements[] = $userA = (new Node())
            ->addLabel('User')
            ->addProperty('id', '6ce3006b-6b7f-4770-8075-d2bf91804d14')
            ->addProperty('name', 'User A')
            ->addProperty('email', 'user-a@localhost.de')
            ->addIdentifier('id');

        $elements[] = $groupA = (new Node())
            ->addLabel('Group')
            ->addProperty('id', '13ba4480-1310-44b1-9ebd-b0236069d250')
            ->addProperty('name', 'Group A')
            ->addIdentifier('id');

        $elements[] = $userAIsPartOfGroup = (new Relation())
            ->setStartNode($userA)
            ->setEndNode($groupA)
            ->setType('PART_OF_GROUP')
            ->addProperty('id', 'b430e5ba-93b7-44e1-895b-c7120e11958f')
            ->addIdentifier('id');

        $elements[] = $userB = (new Node())
            ->addLabel('User')
            ->addProperty('id', '4ecb295f-693e-423b-9c7e-1994158d1da8')
            ->addProperty('name', 'User B')
            ->addProperty('email', 'user-b@localhost.de')
            ->addIdentifier('id');

        $elements[] = $groupB = (new Node())
            ->addLabel('Group')
            ->addProperty('id', '40ac6938-377c-4541-9b68-8604d09f43c4')
            ->addProperty('name', 'Group B')
            ->addIdentifier('id');

        $elements[] = $userBIsPartOfGroupB = (new Relation())
            ->setStartNode($userB)
            ->setEndNode($groupB)
            ->setType('PART_OF_GROUP')
            ->addProperty('id', 'd67e3ff1-ffc9-47db-8e11-bee442b4cff8')
            ->addIdentifier('id');

        $elements[] = $groupC = (new Node())
            ->addLabel('Group')
            ->addProperty('id', '490e9e62-11e6-4027-b93b-2a19f730a85f')
            ->addProperty('name', 'Group C')
            ->addIdentifier('id');

        $elements[] = $userBIsPartOfGroupC = (new Relation())
            ->setStartNode($userB)
            ->setEndNode($groupC)
            ->setType('PART_OF_GROUP')
            ->addProperty('id', '3ca250fc-d9c5-4753-bd8b-37c7d4b5b8c2')
            ->addIdentifier('id');

        $elements[] = $groupD = (new Node())
            ->addLabel('Group')
            ->addProperty('id', '8de31fc7-dbfb-459b-99d8-040fba949a04')
            ->addProperty('name', 'Group D')
            ->addIdentifier('id');

        $elements[] = $groupCIsPartOfGroupD = (new Relation())
            ->setStartNode($groupC)
            ->setEndNode($groupD)
            ->setType('PART_OF_GROUP')
            ->addProperty('id', 'f993a394-71a7-40c0-9d79-ccf050e4c3fe')
            ->addIdentifier('id');

        $elements[] = $data = (new Node())
            ->addLabel('Data')
            ->addProperty('id', 'e65c2224-3949-455a-b08a-cf150348982a')
            ->addProperty('name', 'Data')
            ->addIdentifier('id');

        $elements[] = $groupAOwnsData = (new Relation())
            ->setStartNode($groupA)
            ->setEndNode($data)
            ->setType('OWNS')
            ->addProperty('id', '582989de-e3a0-4b25-8caa-c7c0d2638dae')
            ->addIdentifier('id');

        $elements[] = $subData = (new Node())
            ->addLabel('Data')
            ->addProperty('id', 'd95b284a-734f-4a87-934f-6e901a51322b')
            ->addProperty('name', 'SubData')
            ->addIdentifier('id');

        $elements[] = $dataOwnsSubData = (new Relation())
            ->setStartNode($data)
            ->setEndNode($subData)
            ->setType('OWNS')
            ->addProperty('id', 'ffa22051-919e-4349-9148-0989b50c8a05')
            ->addIdentifier('id');

        $elements[] = $readRule = (new Node())
            ->addLabel('Rule')
            ->addProperty('id', 'f23c41b7-3afb-4541-9073-cc1e26a6ec81')
            ->addProperty('name', 'Read Rule')
            ->addIdentifier('id');

        $elements[] = $groupBHasRuleReadRule = (new Relation())
            ->setStartNode($groupB)
            ->setEndNode($readRule)
            ->setType('HAS_RULE')
            ->addProperty('id', '068efa2f-b07d-4251-814c-371cf53605a1')
            ->addIdentifier('id');

        $elements[] = $readRuleAppliesOnData = (new Relation())
            ->setStartNode($readRule)
            ->setEndNode($data)
            ->setType('APPLIES')
            ->addProperty('id', 'c9f3e69c-7055-4101-bcfa-17d28377a860')
            ->addIdentifier('id');

        $elements[] = $readAction = (new Node())
            ->addLabel('Action')
            ->addProperty('id', '033cef01-eeb6-4503-9903-66138aee2666')
            ->addProperty('name', 'Read Action')
            ->addIdentifier('id');

        $elements[] = $readRuleHasActionReadAction = (new Relation())
            ->setStartNode($readRule)
            ->setEndNode($readAction)
            ->setType('HAS_ACTION')
            ->addProperty('id', '1b0dee36-5553-474d-a4b9-a5b005327382')
            ->addIdentifier('id');

        $elements[] = $ruleA = (new Node())
            ->addLabel('Rule')
            ->addProperty('id', 'b3387a93-689e-4ea2-b65a-689e5871383b')
            ->addProperty('name', 'Rule A')
            ->addIdentifier('id');

        $elements[] = $groupDHasRuleA = (new Relation())
            ->setStartNode($groupD)
            ->setEndNode($ruleA)
            ->setType('HAS_RULE')
            ->addProperty('id', 'be40b9f8-f94e-4e78-b43b-86d40d4aeeb2')
            ->addIdentifier('id');

        $elements[] = $ruleB = (new Node())
            ->addLabel('Rule')
            ->addProperty('id', 'c2e2765c-c9f0-450e-a8d6-cbe8a9ccb370')
            ->addProperty('name', 'Rule B')
            ->addIdentifier('id');

        $elements[] = $stateNull = (new Node())
            ->addLabel('State')
            ->addProperty('id', '2f5640d7-d33f-4cf0-a5f3-2656efd156c0')
            ->addProperty('name', 'State Null')
            ->addIdentifier('id');

        $elements[] = $stateA = (new Node())
            ->addLabel('State')
            ->addProperty('id', 'e420806d-4542-47fb-97e2-cd029435a4f1')
            ->addProperty('name', 'State A')
            ->addIdentifier('id');

        $elements[] = $stateB = (new Node())
            ->addLabel('State')
            ->addProperty('id', '802a7aba-a505-4fb9-86a6-eb299c0e24bb')
            ->addProperty('name', 'State B')
            ->addIdentifier('id');

        $elements[] = $transitionFromStateAToB = (new Node())
            ->addLabel('Transition')
            ->addProperty('id', 'cba81fde-309c-437a-bb6d-831baab8c600')
            ->addProperty('name', 'A to B')
            ->addIdentifier('id');

        $elements[] = $transitionFromStateBToA = (new Node())
            ->addLabel('Transition')
            ->addProperty('id', 'ecad1b63-2060-4885-afd1-3c9a84d3f997')
            ->addProperty('name', 'B to A')
            ->addIdentifier('id');

        $elements[] = $transitionFromStateAToBFrom = (new Relation())
            ->setStartNode($stateA)
            ->setEndNode($transitionFromStateAToB)
            ->setType('TRANSITION_FROM')
            ->addProperty('id', '4458cef9-41c7-4e47-8fc8-7e3c2b26586d')
            ->addIdentifier('id');

        $elements[] = $transitionFromStateAToBTo = (new Relation())
            ->setStartNode($transitionFromStateAToB)
            ->setEndNode($stateB)
            ->setType('TRANSITION_TO')
            ->addProperty('id', 'a661b0b8-5d86-468b-b399-bd4d0ef35b1d')
            ->addIdentifier('id');

        $elements[] = $transitionFromStateBToAFrom = (new Relation())
            ->setStartNode($stateB)
            ->setEndNode($transitionFromStateBToA)
            ->setType('TRANSITION_FROM')
            ->addProperty('id', '65cd6075-6bc5-449b-8f57-54d7b96a3f95')
            ->addIdentifier('id');

        $elements[] = $transitionFromStateBToATo = (new Relation())
            ->setStartNode($transitionFromStateBToA)
            ->setEndNode($stateA)
            ->setType('TRANSITION_TO')
            ->addProperty('id', '8f30f486-6f27-47fd-972e-e69608fa4763')
            ->addIdentifier('id');

        $elements[] = $ruleAHasTransitionStateAToB = (new Relation())
            ->setStartNode($ruleA)
            ->setEndNode($transitionFromStateAToB)
            ->setType('HAS_TRANSITION')
            ->addProperty('id', '66f2acd5-0284-4fed-a608-f6490c55cd42')
            ->addIdentifier('id');

        $elements[] = $userBHasRuleB = (new Relation())
            ->setStartNode($userB)
            ->setEndNode($ruleB)
            ->setType('HAS_RULE')
            ->addProperty('id', '7d192ce4-be17-441d-b9e8-577d9599cb5f')
            ->addIdentifier('id');

        $this->cypherEntityManager->run('MATCH (n) DETACH DELETE n;');
        $this->mongoEntityManager->getClient()->dropDatabase('tion');

        foreach ($elements as $element) {
            $this->cypherEntityManager->create($element);
        }
        $this->cypherEntityManager->flush();

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

        $user = $this->userRepository->getUserByUuid(UuidV4::fromString('4ecb295f-693e-423b-9c7e-1994158d1da8'));
        print_r($user);

        $collection = $this->mongoEntityManager->getClient()->tion->users;

        $insertOneResult = $collection->insertOne([
            '_id' => '03ba8639-9232-4c25-923d-0dc2b3900f32',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'name' => 'Admin User',
        ]);

        printf("Inserted %d document(s)\n", $insertOneResult->getInsertedCount());

        var_dump($insertOneResult->getInsertedId());

        $output->writeln('hello world :D');

        return Command::SUCCESS;
    }
}
