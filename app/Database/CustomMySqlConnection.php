<?php

namespace App\Database;

use Illuminate\Database\MySqlConnection;

class CustomMySqlConnection extends MySqlConnection
{
    /**
     * Get a new query builder instance.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        return new CustomQueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }
}
