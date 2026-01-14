<?php

namespace Tests\Unit\Support;

class FakeChartAccountQuery
{
    public array $firstByWhereIn = [];
    public array $firstByWhere = [];
    private ?array $lastWhereIn = null;
    private ?array $lastWhere = null;

    public function where(string $column, $operatorOrValue, $value = null): self
    {
        if (func_num_args() === 2) {
            $operator = '=';
            $value = $operatorOrValue;
        } else {
            $operator = $operatorOrValue;
        }

        $this->lastWhere = [$column, $operator, $value];

        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $this->lastWhereIn = [$column, $values];

        return $this;
    }

    public function first()
    {
        if ($this->lastWhereIn) {
            $key = $this->whereInKey($this->lastWhereIn[0], $this->lastWhereIn[1]);
            if (array_key_exists($key, $this->firstByWhereIn)) {
                return $this->firstByWhereIn[$key];
            }
        }

        if ($this->lastWhere) {
            $key = $this->whereKey($this->lastWhere[0], $this->lastWhere[1], $this->lastWhere[2]);
            if (array_key_exists($key, $this->firstByWhere)) {
                return $this->firstByWhere[$key];
            }
        }

        return null;
    }

    private function whereInKey(string $column, array $values): string
    {
        return $column . ':' . implode('|', $values);
    }

    private function whereKey(string $column, string $operator, $value): string
    {
        if (is_array($value)) {
            $value = implode('|', $value);
        }

        return $column . ':' . $operator . ':' . $value;
    }
}
