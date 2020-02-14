<?php
declare(strict_types=1);

namespace JsonApi\Binders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class JsonApiBinder
 * @package JsonApi\Binders;
 */
class JsonApiBinder
{
    /**
     * @var $this
     */
    private static self $instance;

    /**
     * @var array
     */
    private array $config;

    /**
     * JsonApiBinder constructor.
     */
    private function __construct()
    {
        static::$instance = $this;

        $this->config = config('resources', ['resources' => [], 'reverse' => []]);
    }

    /**
     * @return static
     */
    public static function get(): self
    {
        return static::$instance ?? new static();
    }

    /**
     * @param $object
     * @param array $parameters
     * @return Model
     */
    public function makeModel($object, ...$parameters): Model
    {
        return $this->getModelClass($object)::make(...$parameters);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->config['resources']);
    }

    /**
     * @param $object
     * @param $id
     * @return Model
     */
    public function findOrFail($object, $id): Model
    {
        return $this->getModelClass($object)::findOrFail($id);
    }

    /**
     * @param $object
     * @param $id
     * @return Model
     */
    public function findModel($object, $id): ?Model
    {
        return $this->getModelClass($object)::find($id);
    }

    /**
     * @param mixed $object
     * @return Model
     */
    public function getModelClass($object): string
    {
        return $this->getData($object)['model'];
    }

    /**
     * @param mixed $object
     * @return JsonResource|string
     */
    public function getResourceClass($object): string
    {
        return $this->getData($object)['resource'];
    }

    /**
     * @param mixed $object
     * @param array $parameters
     * @return JsonResource
     */
    public function makeResource($object, ...$parameters): JsonResource
    {
        return $this->getResourceClass($object)::make($object, ...$parameters);
    }

    /**
     * @param Model $model
     * @return array
     */
    public function getIdentifier(Model $model): array
    {
        return ['type' => $this->getName($model), 'id' => $model->getKey()];
    }

    /**
     * @param mixed $object
     * @param bool $strict
     * @return string
     */
    public function getName($object, bool $strict = true): ?string
    {
        if (in_array($object, array_keys($this->config['resources']))) {
            return $object;
        } else {
            if (is_string($object)) {
                $class = $object;
            } else {
                $class = get_class($object);
            }

            if (!array_key_exists($class, $this->config['reverse']) && $strict) {
                abort(500, "The class '$class' has no associated resource name.");
            }

            return $this->config['reverse'][$class] ?? null;
        }
    }

    /**
     * @param $object
     * @return bool
     */
    public function isResolvable($object): bool
    {
        return $this->getName($object, false) ? true : false;
    }

    /**
     * @return array
     */
    public function getResources(): array
    {
        return $this->config['resources'];
    }

    /**
     * @param $name
     * @return array
     */
    private function getData($name): array
    {
        if (!is_string($name) || !array_key_exists($name, $this->config['resources'])) {
            $name = $this->getName($name);
        }

        return $this->config['resources'][$name];
    }
}
