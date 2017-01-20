<?php
namespace Chunker;

use DB;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class Chunker
 *
 * Allows you to chunk incrementing models, make modifications, add records, and delete records without
 * affecting the chunkable dataset.
 *
 * @author Devon Bessemer
 * @package App\Classes
 */
class Chunker
{

    /**
     * @var Builder
     */
    protected $query;

    /**
     * @var int
     */
    protected $count;

    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * @var string
     */
    protected $connection;

    /**
     * @var string
     */
    protected $primaryKey;

    /**
     * @var int
     */
    protected $maxId;

    /**
     * @var int
     */
    protected $minId;

    /**
     * @var int
     */
    protected $lastId = 0;

    /**
     * @var bool
     */
    protected $queryLogEnabled = false;

    /**
     * ChunkAndEdit constructor.
     * @param Builder $query
     * @param $count
     * @param callable $callback
     * @throws \Exception
     */
    public function __construct(Builder $query, $count, callable $callback)
    {
        $this->query = $query;
        $this->count = $count;
        $this->callback = $callback;
        $this->retreiveModelInformation();
        $this->setMinId();
        $this->setMaxId();
        $this->process();
    }

    protected function retreiveModelInformation() {
        $this->model = $this->query->getModel();
        $this->primaryKey = $this->model->getKeyName();
        $this->connection = $this->model->getConnectionName();
        $this->queryLogEnabled = DB::connection($this->connection)->logging();
        if (!$this->model->getIncrementing()) {
            throw new \Exception('ChunkAndEdit Exception: Model must have an auto incrementing primary key.');
        }
    }

    protected function setMinId() {
        $this->minId = with(clone $this->query)->min($this->primaryKey);
    }

    protected function setMaxId() {
        $this->maxId = with(clone $this->query)->max($this->primaryKey);
    }

    protected function process() {
        if (!$this->maxId) {
            return;
        }

        if ($this->queryLogEnabled) {
            $this->disableQueryLog();
        }

        while($this->lastId !== null && $this->lastId < $this->maxId) {
            $results = $this->getNextChunk();
            $this->lastId = $results->max($this->primaryKey);
            call_user_func($this->callback, $results);
        }

        if ($this->queryLogEnabled) {
            $this->enableQueryLog();
        }
    }

    protected function getNextChunk() {
        return with(clone $this->query)
            ->where($this->primaryKey, '>', $this->lastId)
            ->limit($this->count)
            ->get();
    }

    /**
     * Reduces memory usage
     */
    protected function disableQueryLog() {
        DB::connection($this->connection)->disableQueryLog();
    }

    protected function enableQueryLog() {
        DB::connection($this->connection)->enableQueryLog();
    }
}