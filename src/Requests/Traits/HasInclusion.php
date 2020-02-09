<?php
declare(strict_types=1);

namespace JsonApi\Requests\Traits;

use JsonApi\Requests\Params\Inclusion;

/**
 * Trait HasInclusion
 * @package App\JsonApi\Request\Traits
 */
trait HasInclusion
{
    /**
     * @var Inclusion
     */
    private Inclusion $inclusion;

    /**
     * @return Inclusion
     */
    public final function getInclusions(): Inclusion
    {
        return $this->inclusion;
    }

    protected final function initializeInclusion()
    {
        $this->rules(['include' => 'string']);

        $this->afterValidation(function () {
            $this->inclusion = Inclusion::makeFromString($this->get('include', ''));
        });
    }
}
