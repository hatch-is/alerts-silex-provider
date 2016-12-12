<?php

namespace Alerts;

class Filter
{
    public function parse($filter)
    {
        $parsedQueries = [];

        if (isset($filter['limit'])) {
            $parsedQueries['$limit'] = $filter['limit'];
        }

        if (isset($filter['skip'])) {
            $parsedQueries['$skip'] = $filter['skip'];
        }

        if (isset($filter['where'])) {
            $parsedWhere = $this->parseWhere($filter['where']);
            $parsedQueries = array_merge($parsedQueries, $parsedWhere);
        }

        if (isset($filter['sort'])) {
            $parsedQueries['$sort'] = $this->parseSort($filter['sort']);
        }

        return $parsedQueries;
    }

    public function parseSort($sort)
    {
        $parsedSort = [];
        foreach ($sort as $key => $item) {
            if (!is_array($item)) {
                $parsedSort[$key] = strtolower($item) == 'desc' ? -1 : 1;
            }
        }
        return $parsedSort;
    }

    public function parseWhere($where)
    {
        $parsedWhere = [];
        foreach ($where as $key => $item) {
            if (is_string($key)
                && in_array($key, ['and', 'or', 'gt', 'gte', 'lt', 'lte'])
            ) {
                $key = '$' . $key;
            }
            if (is_array($item)) {
                $item = $this->parseWhere($item);
            }

            $parsedWhere[$key] = $item;
        }

        return $parsedWhere;
    }
}