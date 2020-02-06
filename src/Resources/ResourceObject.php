<?php
declare(strict_types=1);

namespace App\JsonApi\Resources;

use App\JsonApi\Models\JsonApiModel;
use App\JsonApi\Requests\Params\Inclusion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Class ResourceObject
 * @package App\JsonApi\Resources
 */
abstract class ResourceObject extends JsonResource
{
    /**
     * @var Inclusion
     */
    private $inclusion;

    /**
     * Create a new resource instance.
     *
     * @param mixed $resource
     * @param Inclusion $inclusion
     */
    public function __construct($resource, ?Inclusion $inclusion = null)
    {
        parent::__construct($resource);

        $this->inclusion = $inclusion ?? Inclusion::make();
    }

    /**
     * @param array $parameters
     * @return ResourceObject|JsonResource
     */
    public static function make(...$parameters)
    {
        $model = $parameters[0] ?? null;

        if (self::class === static::class) {
            return static::makeFromName(JsonApiModel::getName($model), ...$parameters);
        } else {
            return new static(...$parameters);
        }
    }

    /**
     * @param string $name
     * @return string|ResourceObject
     */
    public static function getClassFromName(string $name): string
    {
        return config('resources.nameToResource')[$name];
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public final function toArray($request)
    {
        return [
            'type' => JsonApiModel::getName($this->resource),
            'id' => $this->resource->getKey(),
            'attributes' => $this->toAttributes($request),
            'relationships' => $this->toRelationships(),
            'links' => $this->toLinks(),
            'meta' => [],
        ];
    }

    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param Request $request
     * @return array
     */
    public function with($request)
    {
        return [
            'meta' => config('app.meta'),
            'included' => IncludedObject::make($this->resource, $this->inclusion),
        ];
    }

    /**
     * @param Request $request
     * @return array
     */
    protected abstract function toAttributes(Request $request): array;

    /**
     * @param string $name
     * @param mixed ...$parameters
     * @return ResourceObject|JsonResource
     */
    private static function makeFromName(string $name, ...$parameters)
    {
        return static::getClassFromName($name)::make(...$parameters);
    }

    /**
     * @return Collection
     */
    private final function toRelationships()
    {
        return $this->inclusion->mapWithKeys(function (string $relationName) {
            return [$relationName => RelationshipObject::make($this->resource, $relationName)];
        });
    }

    /**
     * @return array
     */
    private final function toLinks(): array
    {
        return [
            'self' => route(JsonApiModel::getName($this->resource) . ".id", $this->resource->getKey())
        ];
    }
}
