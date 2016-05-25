<?php

namespace Bolt\Extension\Ohlandt\UserProfiles\Storage\Schema\Table;

use Bolt\Storage\Database\Schema\Table\Users;

class UsersTable extends Users
{
    protected function addColumns()
    {
        parent::addColumns();

        $this->table->addColumn('koalas',       'string',     ['length' => 32]);
    }
}