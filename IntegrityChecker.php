<?php

namespace Bolt\Extension\Ohlandt\UserProfiles;

use Bolt\Application;
use Bolt\Database\IntegrityChecker as BoltIntegrityChecker;
use Doctrine\DBAL\Schema\Schema;

class IntegrityChecker extends BoltIntegrityChecker
{
    protected $app;

    protected $config;

    /**
     * IntegrityChecker constructor.
     *
     * @param Application $app
     * @param array $config
     */
    public function __construct(Application $app, array $config)
    {
        $this->app = $app;

        $this->config = $config;

        parent::__construct($app);
    }

    /**
     * Add columns to the bolt_users table based on the extension config
     *
     * @param Schema $schema
     * @return \Doctrine\DBAL\Schema\Table[]
     */
    protected function getBoltTablesSchema(Schema $schema)
    {
        $tables = parent::getBoltTablesSchema($schema);

        foreach ($tables as $table) {
            if ($table->getName() == 'bolt_users') {

                foreach ($this->config['fields'] as $key => $values) {

                    switch ($values['type']) {
                        case 'text':
                            $table->addColumn($key, 'string', array('length' => 256, 'default' => ''));
                            break;
                        case 'textarea':
                            $table->addColumn($key, 'text', array('default' => ''));
                            break;
                        default:
                            $table->addColumn($key, 'text', array('default' => ''));
                    }

                }
            }
        }

        return $tables;
    }
}

