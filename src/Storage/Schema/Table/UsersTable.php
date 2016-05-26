<?php

namespace Bolt\Extension\Ohlandt\UserProfiles\Storage\Schema\Table;

use Bolt\Storage\Database\Schema\Table\Users;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class UsersTable extends Users
{
    private $config;

    /**
     * UsersTable constructor
     *
     * @param AbstractPlatform $platform
     * @param string $tablePrefix
     * @param array $config
     */
    public function __construct(AbstractPlatform $platform, $tablePrefix, array $config)
    {
        parent::__construct($platform, $tablePrefix);

        $this->config = $config;
    }

    /**
     *  Add custom fields to the users table schema
     */
    protected function addColumns()
    {
        parent::addColumns();

        foreach ($this->config['fields'] as $key => $values) {

            switch ($values['type']) {
                case 'text':
                    $this->table->addColumn($key, 'string', array('length' => 256, 'default' => ''));
                    break;
                case 'textarea':
                case 'select':
                    $this->table->addColumn($key, 'text', array('default' => ''));
                    break;
                case 'checkbox':
                    $this->table->addColumn($key, 'boolean', array('default' => 0));
                    break;
                default:
                    $this->table->addColumn($key, 'text', array('default' => ''));
            }

        }
    }
}
