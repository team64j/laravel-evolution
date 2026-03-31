<?php

namespace EvolutionCMS\Legacy;

use ArrayIterator;
use Illuminate\Support\Fluent;
use Traversable;

class Session extends Fluent
{
    /**
     * Get an attribute from the fluent instance.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function value($key, $default = null)
    {
        if (session()->has($key)) {
            return session()->get($key);
        }

        return value($default);
    }

    /**
     * Get an attribute from the fluent instance using "dot" notation.
     *
     * @template TGetDefault
     *
     * @param TKey $key
     * @param TGetDefault|(\Closure(): TGetDefault) $default
     *
     * @return TValue|TGetDefault
     */
    public function get($key, $default = null)
    {
        return session()->get($key, $default);
    }

    /**
     * Set an attribute on the fluent instance using "dot" notation.
     *
     * @param TKey $key
     * @param TValue $value
     *
     * @return $this
     */
    public function set($key, $value)
    {
        session()->put($key, $value);

        return $this;
    }

    /**
     * Fill the fluent instance with an array of attributes.
     *
     * @param iterable<TKey, TValue> $attributes
     *
     * @return $this
     */
    public function fill($attributes)
    {
        foreach ($attributes as $key => $value) {
            session()->put($key, $value);
        }

        return $this;
    }

    /**
     * Get the attributes from the fluent instance.
     *
     * @return array<TKey, TValue>
     */
    public function getAttributes()
    {
        return session()->all();
    }

    /**
     * Convert the fluent instance to an array.
     *
     * @return array<TKey, TValue>
     */
    public function toArray()
    {
        return session()->all();
    }

    /**
     * Determine if the fluent instance is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty(session()->all());
    }

    /**
     * Determine if the given offset exists.
     *
     * @param TKey $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return session()->has($offset);
    }

    /**
     * Set the value at the given offset.
     *
     * @param TKey $offset
     * @param TValue $value
     *
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        session()->put($offset, $value);
    }

    /**
     * Unset the value at the given offset.
     *
     * @param TKey $offset
     *
     * @return void
     */
    public function offsetUnset($offset): void
    {
        session()->forget($offset);
    }

    /**
     * Get an iterator for the attributes.
     *
     * @return ArrayIterator<TKey, TValue>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator(session()->all());
    }

    /**
     * Handle dynamic calls to the fluent instance to set attributes.
     *
     * @param TKey $method
     * @param array{0: ?TValue} $parameters
     *
     * @return $this
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        session()->put($method, count($parameters) > 0 ? array_first($parameters) : true);

        return $this;
    }
}