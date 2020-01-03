<?php
declare(strict_types=1);

namespace ZoiloMora\Doctrine\DBAL\Platforms;

use Doctrine\DBAL\Platforms\SQLServerPlatform;

class MsAccessPlatform extends SQLServerPlatform
{
    /**
     * {@inheritdoc}
     */
    public function getListTablesSQL()
    {
        return 'SELECT MSysObjects.Name, MSysObjects.Type, MSysObjects.Flags FROM MSysObjects ORDER BY MSysObjects.Name';
    }

    /**
     * {@inheritDoc}
     * @see https://github.com/doctrine/dbal/blob/master/lib/Doctrine/DBAL/Platforms/SQLServerPlatform.php
     */
    protected function doModifyLimitQuery($query, $limit, $offset = null)
    {
        $where = [];

        if ($offset > 0) {
            $where[] = sprintf('doctrine_rownum >= %d', $offset + 1);
        }

        if ($limit !== null) {
            $where[] = sprintf('doctrine_rownum <= %d', $offset + $limit);
            $top = sprintf('TOP %d', $offset + $limit);
        } else {
            $top = 'TOP 9223372036854775807';
        }

        if (empty($where)) {
            return $query;
        }

        // We'll find a SELECT or SELECT distinct and prepend TOP n to it
        // Even if the TOP n is very large, the use of a CTE will
        // allow the SQL Server query planner to optimize it so it doesn't
        // actually scan the entire range covered by the TOP clause.
        if (!preg_match('/^(\s*SELECT\s+(?:DISTINCT\s+)?)(.*)$/is', $query, $matches)) {
            return $query;
        }

        $query = $matches[1] . $top . ' ' . $matches[2];

        if (stristr($query, 'ORDER BY')) {
            // Inner order by is not valid in SQL Server for our purposes
            // unless it's in a TOP N subquery.
            $query = $this->scrubInnerOrderBy($query);
        }

        return $query;
    }

    /**
     * {@inheritDoc}
     * @see https://github.com/doctrine/dbal/blob/master/lib/Doctrine/DBAL/Platforms/SQLServerPlatform.php
     */
    private function scrubInnerOrderBy($query)
    {
        $count = substr_count(strtoupper($query), 'ORDER BY');
        $offset = 0;

        while ($count-- > 0) {
            $orderByPos = stripos($query, ' ORDER BY', $offset);
            if ($orderByPos === false) {
                break;
            }

            $qLen = strlen($query);
            $parenCount = 0;
            $currentPosition = $orderByPos;

            while ($parenCount >= 0 && $currentPosition < $qLen) {
                if ($query[$currentPosition] === '(') {
                    $parenCount++;
                } elseif ($query[$currentPosition] === ')') {
                    $parenCount--;
                }

                $currentPosition++;
            }

            if ($this->isOrderByInTopNSubquery($query, $orderByPos)) {
                // If the order by clause is in a TOP N subquery, do not remove
                // it and continue iteration from the current position.
                $offset = $currentPosition;
                continue;
            }

            if ($currentPosition >= $qLen - 1) {
                continue;
            }

            $query = substr($query, 0, $orderByPos) . substr($query, $currentPosition - 1);
            $offset = $orderByPos;
        }

        return $query;
    }

    /**
     * {@inheritDoc}
     * @see https://github.com/doctrine/dbal/blob/master/lib/Doctrine/DBAL/Platforms/SQLServerPlatform.php
     */
    private function isOrderByInTopNSubquery($query, $currentPosition)
    {
        // Grab query text on the same nesting level as the ORDER BY clause we're examining.
        $subQueryBuffer = '';
        $parenCount = 0;

        // If $parenCount goes negative, we've exited the subquery we're examining.
        // If $currentPosition goes negative, we've reached the beginning of the query.
        while ($parenCount >= 0 && $currentPosition >= 0) {
            if ($query[$currentPosition] === '(') {
                $parenCount--;
            } elseif ($query[$currentPosition] === ')') {
                $parenCount++;
            }

            // Only yank query text on the same nesting level as the ORDER BY clause.
            $subQueryBuffer = ($parenCount === 0 ? $query[$currentPosition] : ' ') . $subQueryBuffer;

            $currentPosition--;
        }

        return (bool)preg_match('/SELECT\s+(DISTINCT\s+)?TOP\s/i', $subQueryBuffer);
    }

    /**
     * {@inheritDoc}
     */
    public function getListTableColumnsSQL($table, $database = null)
    {
        // TODO Build sql to get list of columns.
        return [];
    }
}
