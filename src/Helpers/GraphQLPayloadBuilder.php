<?php

namespace Convenia\GraphQLClient\Helpers;

/**
 * GraphQLPayloadBuilder
 */
class GraphQLPayloadBuilder
{
    protected $queryType;
    protected $enums;
    protected $baseUrl;
    protected $queryName;
    protected $outputParams;

    public function __construct($query)
    {
        $this->queryType = $query->queryType;
        $this->queryName = $query->getQueryName();
        $this->outputParams = $query->getOutputParams();
        $this->enums = $query->enums;
    }

    /**
     * Do the formatting of the input parameters and output fields into a GraphQL string
     *
     * @param array $arguments Input Arguments to send
     * @param array $fields Desired output fields, optional. If null the variable $outputParams of every query or mutation will be used as desired output
     *
     * @return string  The graphQl query string
     */
    public function buildGraph(array $arguments, $fields = null)
    {
        $arguments = $this->buildArguments($arguments);
        $graph = $this->createGraph($fields);

        return "{$this->queryType} { {$this->queryName}({$arguments}) {$graph} }";
    }

    /**
     * Do the formatting of the input parameters and output fields without fields into a GraphQL string
     *
     * @param array $arguments
     * @return string
     */
    public function buildGraphWithoutFields(array $arguments)
    {
        $arguments = $this->buildArguments($arguments);

        return "{$this->queryType} { {$this->queryName}({$arguments}) }";
    }

    /**
     * Do the formatting of the input parameters and output fields into a GraphQL update
     *
     * @param  int $id
     * @param  array $arguments
     * @param  array|null $fields
     * @return String
     */
    public function buildUpdate($id, array $arguments, $fields = null)
    {
        $arguments = $this->buildArguments($arguments);
        $graph = $this->createGraph($fields);

        return "{$this->queryType} { {$this->queryName}(id:{$id} {$arguments}) {$graph} }";
    }

    /**
     * Do the formatting of the input parameters and output fields into a GraphQL list query
     *
     * @param  array $fields
     * @return string
     */
    public function buildList($fields)
    {
        $fields = $this->createGraph($fields);

        return "{$this->queryType} { {$this->queryName} { {$fields} } }";
    }

    /**
     * Do the formatting of the input parameters and output fields into a GraphQL single
     *
     * @param int $id
     * @param  array $fields
     * @return string
     */
    public function buildSingle($id, $fields)
    {
        $fields = $this->createGraph($fields);

        return "{$this->queryType} { {$this->queryName}(id:{$id}) {$fields} }";
    }

    /**
     * Do the formatting of the input parameters and output fields into a GraphQL pagination
     *
     * @param integer $limit limit per page
     * @param integer $page page number
     * @param array $fields output fields
     * @return string
     */
    public function buildPaginate($limit = 1, $page = 1, $fields)
    {
        $fields = $this->createGraph($fields);

        return "{$this->queryType} { {$this->queryName}(limit:{$limit},page:{$page}){data{$fields}},total,per_page }";
    }

    /**
     * Build up all the arguments to send
     *
     * @param array $data Input arguments to send
     * @return string
     */
    protected function buildArguments($data)
    {
        $arguments = substr(json_encode($data, JSON_UNESCAPED_UNICODE), 1, -1);
        $arguments = preg_replace('/"([^"]+)"\s*:\s*/', '$1:', $arguments);

        if (empty($this->enums)) {
            return $arguments;
        }

        return $this->buildEnums($data, $arguments);
    }

    /**
     * Build up all enums to use inside
     *
     * @param $data
     * @param $arguments
     * @return string
     */
    protected function buildEnums($data, $arguments): string
    {
        $enums = array_intersect_key(
            $data,
            array_flip($this->enums)
        );

        $oldValues = array_map(function ($enum) {
            return '"' . $enum . '"';
        }, $enums);

        $oldValues = array_values($oldValues);
        $newValues = array_values($enums);

        return str_replace($oldValues, $newValues, $arguments);
    }

    /**
     * Create a GraphQL query string
     *
     * @param array $data
     * @return string
     */
    protected function createGraph(array $data): string
    {
        $data = empty($data) ? $this->outputParams : $data;

        $graph = "";
        foreach ($data as $query => $node) {
            if (is_array($node)) {
                $graph .= "{$query} ";
                $graph .= $this->createGraph($node);
                continue;
            }
            $graph .= "{$node} ";
        }

        return "{ {$graph} } ";
    }

    /**
     * Build up a search query
     *
     * @param int $limit
     * @param int $page
     * @param $arguments
     * @param $fields
     * @return string
     */
    public function buildSearch($limit = 1, $page = 1, $arguments, $fields)
    {
        $arguments = $this->buildArguments($arguments);
        $fields = $this->createGraph($fields);

        return "{$this->queryType} { {$this->queryName}(limit:{$limit},page:{$page},{$arguments}){data {$fields} }}";
    }
}
