<?php

namespace Doctrine\Tests\ODM\PHPCR;

use Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\PHPCR\DocumentManager;

abstract class PHPCRFunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    public function createDocumentManager(array $paths = null)
    {
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $reader->addGlobalIgnoredName('group');

        if (empty($paths)) {
            $paths = array(__DIR__ . "/../../Models");
        }

        $metaDriver = new AnnotationDriver($reader, $paths);

        $factoryclass = isset($GLOBALS['DOCTRINE_PHPCR_FACTORY'])
            ? $GLOBALS['DOCTRINE_PHPCR_FACTORY'] : '\Jackalope\RepositoryFactoryJackrabbit';

        $parameters = array_intersect_key($GLOBALS, $factoryclass::getConfigurationKeys());
        // factory will return null if it gets unknown parameters

        $repository = $factoryclass::getRepository($parameters);
        $this->assertNotNull($repository, 'There is an issue with your parameters: '.var_export($parameters, true));

        $workspace = isset($GLOBALS['DOCTRINE_PHPCR_WORKSPACE'])
            ? $GLOBALS['DOCTRINE_PHPCR_WORKSPACE'] : 'tests';

        $user = isset($GLOBALS['DOCTRINE_PHPCR_USER'])
            ? $GLOBALS['DOCTRINE_PHPCR_USER'] : '';
        $pass = isset($GLOBALS['DOCTRINE_PHPCR_PASS'])
            ? $GLOBALS['DOCTRINE_PHPCR_PASS'] : '';

        // TODO fix this code
        if ($factoryclass === '\Jackalope\RepositoryFactoryDoctrineDBAL') {
            // TODO: have an option in the DBAL factory to have an in-memory database instead of connection parameters
            $conn = \Doctrine\DBAL\DriverManager::getConnection(array('driver' => 'pdo_sqlite', 'memory' => true));
            $schema = \Jackalope\Transport\DoctrineDBAL\RepositorySchema::create();
            foreach ($schema->toSql($conn->getDatabasePlatform()) as $sql) {
                $conn->exec($sql);
            }
            $transport = new \Jackalope\Transport\DoctrineDBAL($conn);
            $transport->createWorkspace($workspace);
        }

        $credentials = new \PHPCR\SimpleCredentials($user, $pass);
        $session = $repository->login($credentials, $workspace);

        $config = new \Doctrine\ODM\PHPCR\Configuration();
        $config->setMetadataDriverImpl($metaDriver);

        return DocumentManager::create($session, $config);
    }

    public function resetFunctionalNode($dm)
    {
        $session = $dm->getPhpcrSession();
        $root = $session->getNode('/');
        if ($root->hasNode('functional')) {
            $root->getNode('functional')->remove();
            $session->save();
        }

        $node = $root->addNode('functional');
        $session->save();

        $dm->clear();

        return $node;
   }
}
