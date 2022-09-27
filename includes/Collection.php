<?php
/**
 * This class creates an "Array", yes in quotes, of Objects that can be passed around by
 * reference. Standard PHP 5.3 arrays pass values by Value when iterating them
 * and using the reference operator during assignment creates unexpected behavior
 *
 * @author javier
 */
class Collection implements ArrayAccess, IteratorAggregate {
   
    public function offsetExists($offset) {
        return isset($this->{'obj_'.$offset});
    }

    public function offsetGet($offset) {
        return $this->{'obj_'.$offset};
    }

    public function offsetSet($offset, $value) {
        if($offset === null) {
            $offset = $this->count();
        }
        $this->{'obj_'.$offset}=$value;
    }

    public function offsetUnset($offset) {
        unset($this->{'obj_'.$offset});
    }

    public function getIterator() {
        return new ArrayIterator($this);
    }
    public function count() {
        return count(get_object_vars($this));
    }
}