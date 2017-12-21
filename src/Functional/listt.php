<?php

namespace Widmogrod\Functional;

use Widmogrod\FantasyLand\Foldable;
use Widmogrod\Primitive\Listt;
use Widmogrod\Primitive\ListtCons;

const fromIterable = 'Widmogrod\Functional\fromIterable';

// TODO extract
class SnapshotIterator extends \IteratorIterator
{
    private $inMemoryValid;
    private $inMemoryCurrent;
    private $inSnapshot;

    public function valid()
    {
        if (null === $this->inMemoryValid) {
            $this->inMemoryValid = parent::valid();
        }

        return $this->inMemoryValid;
    }

    public function current()
    {
        if (null === $this->inMemoryCurrent) {
            $this->inMemoryCurrent = parent::current();
        }

        return $this->inMemoryCurrent;
    }

    public function snapshot()
    {
        if (null === $this->inSnapshot) {
            $this->inSnapshot = new self($this->getInnerIterator());
            $this->inSnapshot->next();
        }

        return $this->inSnapshot;
    }
}

function fromIterable(iterable $i): Listt
{
    if (is_array($i)) {
        $i = new \ArrayObject($i);
    }

    if ($i instanceof \IteratorAggregate) {
        $i = $i->getIterator();
    }

    if (!($i instanceof SnapshotIterator)) {
        $i = new SnapshotIterator($i);
        $i->rewind();
    }

    return fromSnapshotIterator($i);
}

function fromSnapshotIterator(SnapshotIterator $i): Listt
{
    if (!$i->valid()) {
        return fromNil();
    }

    return ListtCons::of(function () use ($i) {
        return [
            $i->current(),
            fromSnapshotIterator($i->snapshot())
        ];
    });
}

/**
 * @var callable
 */
const fromValue = 'Widmogrod\Functional\fromValue';

function fromValue($value): Listt
{
    return ListtCons::of(function () use ($value) {
        return [$value, fromNil()];
    });
}

function fromNil(): Listt
{
    return ListtCons::mempty();
}

/**
 * @var callable
 */
const concat = 'Widmogrod\Functional\concat';

/**
 * concat :: Foldable t => t [a] -> [a]
 *
 * <code>
 * concat(fromIterable([fromIterable([1, 2]), fromIterable([3, 4])])) == fromIterable([1, 2, 3, 4])
 * </code>
 *
 * concat :: Foldable t => t [a] -> [a]
 * concat xs = build (\c n -> foldr (\x y -> foldr c y x) n xs)
 *
 * build :: forall a. (forall b. (a -> b -> b) -> b -> b) -> [a]
 * build g = g (:) []
 *
 * foldr :: (a -> b -> b) -> b -> [a] -> b
 *
 * The concatenation of all the elements of a container of lists.
 *
 * @param Foldable $xs
 *
 * @return Listt
 */
function concat(Foldable $xs)
{
    return foldr(function ($x, Listt $y) {
        return foldr(prepend, $y, $x);
    }, fromNil(), $xs);
}

/**
 * @var callable
 */
const prepend = 'Widmogrod\Functional\prepend';

/**
 * prepend :: a -> [a] -> [a]
 *
 * @param mixed $x
 * @param Listt $xs
 * @return Listt
 */
function prepend($x, Listt $xs = null)
{
    return curryN(2, function ($x, Listt $xs): Listt {
        return append(fromValue($x), $xs);
    })(...func_get_args());
}

/**
 * @var callable
 */
const append = 'Widmogrod\Functional\append';

/**
 * (++) :: [a] -> [a] -> [a]
 *
 * Append two lists, i.e.,
 *
 *  [x1, ..., xm] ++ [y1, ..., yn] == [x1, ..., xm, y1, ..., yn]
 *  [x1, ..., xm] ++ [y1, ...] == [x1, ..., xm, y1, ...]
 *
 * If the first list is not finite, the result is the first list.
 *
 * @param Listt $a
 * @param Listt|null $b
 * @return Listt|callable
 */
function append(Listt $a, Listt $b = null)
{
    return curryN(2, function (Listt $a, Listt $b): Listt {
        return $a->concat($b);
    })(...func_get_args());
}

/**
 * @var callable
 */
const head = 'Widmogrod\Functional\head';

/**
 * head :: [a] -> a
 *
 * Extract the first element of a list, which must be non-empty.
 *
 * @param Listt $l
 * @return mixed
 * @throws \Widmogrod\Primitive\EmptyListError
 */
function head(Listt $l)
{
    return $l->head();
}

/**
 * @var callable
 */
const tail = 'Widmogrod\Functional\tail';

/**
 * tail :: [a] -> [a]
 *
 * Extract the elements after the head of a list, which must be non-empty.
 *
 * @param Listt $l
 * @return Listt
 * @throws \Widmogrod\Primitive\EmptyListError
 */
function tail(Listt $l)
{
    return $l->tail();
}

/**
 * @var callable
 */
const length = 'Widmogrod\Functional\length';

/**
 * length :: Foldable t => t a -> Int
 *
 * Returns the size/length of a finite structure as an Int.
 * The default implementation is optimized for structures that are similar to cons-lists,
 * because there is no general way to do better.
 *
 * @param Foldable $t
 * @return int
 */
function length(Foldable $t): int
{
    return $t->reduce(function ($len) {
        return $len + 1;
    }, 0);
}
