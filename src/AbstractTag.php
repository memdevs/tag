<?php
/**
 *
 *
 */

namespace memdevs\tag;


/**
 * Class AbstractTag
 *
 * @package models
 */

abstract class AbstractTag
{

  /**
   * @param int $tag_id
   * @param string $tag_value
   * @return bool
   */
  public function hasTagIdValue($tag_id, $tag_value)
  {
    $type = $this->checkTag($tag_id);
    $tag_id = $this->sanitize_int($tag_id);
    if ($type == 'n') {
      throw new \InvalidArgumentException("Tag {$tag_id} does not have a value");
    }
    if ($type == 'j') {
      $tag_value = json_encode($tag_value, JSON_FORCE_OBJECT);
      if ($tag_value === false) {
        throw new \InvalidArgumentException("JSON tag could {$tag_id} not be converted to JSON string");
      }
    }

    return $this->doHasTagIdValue($tag_id, $tag_value);
  }


  /**
   * @param int $tag_id
   * @return bool
   */
  public function hasTagId($tag_id)
  {
    $tag_id = $this->sanitize_int($tag_id);

    return $this->doHasTagId($tag_id);
  }


  /**
   * Can only retrieve unique tags; otherwise the returned value is undefined
   *
   * @param int $tag_id
   * @return array
   */
  public function getTag($tag_id)
  {
    $this->checkTag($tag_id, null, 1);
    $tag_id = $this->sanitize_int($tag_id);

    return $this->doGetTagById($tag_id);
  }


  /**
   * @param int $tag_id
   * @return mixed
   * @throws \Exception
   */
  public function getTagValue($tag_id)
  {
    $type = $this->checkTag($tag_id, null, 1);
    $tag_id = $this->sanitize_int($tag_id);
    $tag = $this->doGetTagById($tag_id);
    if ($tag === false) {
      throw new \Exception("tag_id {$tag_id} cannot be found");
    }

    return $this->convertToType($tag['tag_value'], $type);
  }


  /**
   * @param int $tag_id
   * @param mixed $tag_value
   * @return mixed
   * @throws \Exception
   */
  public function getTagByIdValue($tag_id, $tag_value)
  {
    $type = $this->checkTag($tag_id);
    $tag_id = $this->sanitize_int($tag_id);
    if ($type == 'i' || $type == 'c') {
      $tag_value = $this->sanitize_int($tag_value);
    }
    if ($type == 'n' && !is_null($tag_value)) {
      throw new \InvalidArgumentException("Tag {$tag_id} cannot have a value");
    }
    if ($type == 'j') {
      throw new \InvalidArgumentException("Cannot search on value for JSON tag {$tag_id}");
    }

    $tag = $this->doGetTagByIdValue($tag_id, $tag_value);
    if ($tag === false) {
      throw new \Exception("tag_id {$tag_id} with given value cannot be found");
    }

    return $tag;
  }


  /**
   * Only valid for tags with multiple values
   *
   * @param int $tag_id
   * @return mixed
   * @throws \Exception
   */
  public function getTagValues($tag_id)
  {
    $type = $this->checkTag($tag_id); // Unsure whether we should enforce non-unique tags for this read only operation
    $tag_id = $this->sanitize_int($tag_id);
    $tags = $this->doGetTags($tag_id);
    if (!count($tags)) {
      throw new \Exception("tag_id {$tag_id} cannot be found");
    }
    $values = [];
    foreach ($tags as $tag) {
      $values[] = $this->convertToType($tag['tag_value'], $type);
    }

    return $values;
  }


  /**
   * Retrieves all tags
   */
  public function getAllTags()
  {
    return $this->doGetAllTags();
  }


  /**
   * Retrieves all tags for a specific tag_id
   *
   * @param $tag_id
   * @return array
   */
  public function getTags($tag_id)
  {
    return $this->doGetTags($tag_id);
  }


  /**
   * @param int $tag_id
   * @param string $type
   * @param int $check_if_unique_or_not - null = doesn't matter; 0 = MUST NOT be unique; 1 = MUST be unique.
   * @return mixed
   */
  protected function checkTag($tag_id, $type = null, $check_if_unique_or_not = null)
  {
    $types = $this->doGetTypes();
    if (!isset($types[$tag_id])) {
      throw new \InvalidArgumentException("tag_id {$tag_id} is an invalid tag");
    }
    $tag_type = $types[$tag_id]['tag_type'];
    if ($type && $type != $tag_type) {
      throw new \InvalidArgumentException("tag_id {$tag_id} is not a '{$type}' type of tag");
    }
    if (!is_null($check_if_unique_or_not) && $types[$tag_id]['tag_is_unique'] != $check_if_unique_or_not) {
      if ($check_if_unique_or_not) {
        throw new \InvalidArgumentException("tag_id {$tag_id} must be a unique tag");
      } else {
        throw new \InvalidArgumentException("tag_id {$tag_id} cannot be a unique tag");
      }
    }

    return $tag_type;
  }


  /**
   * Returns whether a tag has to be unique or not
   *
   * @param int $tag_id
   * @return bool
   */
  public function isUniqueTag($tag_id)
  {
    $types = $this->doGetTypes();
    $tag_id = $this->sanitize_int($tag_id);
    if (!isset($types[$tag_id])) {
      throw new \InvalidArgumentException("tag_id {$tag_id} is an invalid tag");
    }

    return $types[$tag_id]['tag_is_unique'] ? true : false;
  }


  /**
   * @param int $tag_id
   * @param int $value
   * @return $this
   */
  public function setCounter($tag_id, $value = 0)
  {
    $this->checkTag($tag_id, 'c', 1);
    $tag_id = $this->sanitize_int($tag_id);
    $value = $this->sanitize_int($value);
    if ($this->hasTagId($tag_id)) {
      $this->doUpdateTag($tag_id, $value);
    } else {
      $this->doAddTag($tag_id, $value);
    }

    return $this;
  }


  /**
   * @param int $tag_id
   * @return $this
   */
  public function resetCounter($tag_id)
  {
    $this->checkTag($tag_id, 'c', 1);
    $this->setCounter($tag_id, 0);

    return $this;
  }


  /**
   * @param int $tag_id
   * @param int $increment
   * @param bool $return_as_value
   * @return AbstractTag|int
   */
  public function incrementCounter($tag_id, $increment = 1, $return_as_value = false)
  {
    $this->checkTag($tag_id, 'c', 1);
    $tag_id = $this->sanitize_int($tag_id);
    $increment = $this->sanitize_int($increment);

    $tag = $this->doGetTagById($tag_id);
    $current_value = $tag !== false ? $this->sanitize_int($tag['tag_value']) : false;

    if ($current_value === false) {
      $current_value = 0;
      $this->addTag($tag_id, $increment);
    } else {
      $this->updateTag($tag_id, $current_value + $increment);
    }

    return $return_as_value ? $current_value + $increment : $this;
  }


  /**
   * @param int $tag_id
   * @param int $decrement
   * @param bool $return_as_value
   * @return AbstractTag|int
   */
  public function decrementCounter($tag_id, $decrement = 1, $return_as_value = false)
  {
    $this->checkTag($tag_id, 'c', 1); // Check it here as the trace indicates it came from decrement, not increment
    return $this->incrementCounter($tag_id, -$decrement, $return_as_value);
  }


  /**
   * @param int $tag_id
   * @param string|null $tag_value
   * @param bool $allow_overwrite
   * @return $this|int
   */
  public function addTag($tag_id, $tag_value = null, $allow_overwrite = false)
  {
    $type = $this->checkTag($tag_id);
    if (!in_array($type, ['i', 'n', 'j', 'c', 't'])) {
      throw new \InvalidArgumentException('Invalid type: only i, n, j, c, t allowed');
    }
    $tag_id = $this->sanitize_int($tag_id);
    if ($type == 'i' || $type == 'c') {
      $tag_value = $this->sanitize_int($tag_value);
    }
    if ($type == 'n' && !is_null($tag_value)) {
      throw new \InvalidArgumentException("Tag {$tag_id} cannot have a value");
    }
    if ($type == 'j') {
      $tag_value = json_encode($tag_value, JSON_FORCE_OBJECT);
      if ($tag_value === false) {
        throw new \InvalidArgumentException("JSON tag could {$tag_id} not be converted to JSON string");
      }
    }

    // If a unique tag we have to allow override for updates
    if ($this->isUniquetag($tag_id)) {
      if ($this->hasTagId($tag_id)) { // Yes, we already have this tag
        if (!$allow_overwrite) { // We cannot update values or timestamps or anything, so return
          return $this;
        }

        return $this->doUpdateTag($tag_id, $tag_value); // Yay! We can update this unique tag!
      } // We now know the tag doesn't exist; safe to continue for adding it
    } else { // Not a unique tag, so we have to check for existing values
      if ($this->hasTagIdValue($tag_id, $tag_value)) { // Yes, we already have this tag AND this value!
        if (!$allow_overwrite) { // We cannot update values or timestamps or anything, so return
          return $this;
        }

        return $this->doUpdateTagValue($tag_id, $tag_value); // Yay! We can refresh this non-unique tag!
      }
    } // Only thing left to do now, is to add!

    return $this->doAddTag($tag_id, $tag_value);
    //    if ($type == 'n' || !$this->hasTagIdValue($tag_id, $tag_value)) {
    //      return $this->doAddTag($tag_id, $tag_value);
    //    } else {
    //      return $this->doUpdateTag($tag_id, $tag_value);
    //    }
    //    return $this;
  }


  /**
   * @param int $tag_id
   * @param string|null $tag_value
   * @return int
   */
  public function updateTag($tag_id, $tag_value = null)
  {
    $type = $this->checkTag($tag_id, null, 1);
    $tag_id = $this->sanitize_int($tag_id);
    //if (!$this->isUniquetag($tag_id)) {
    //  throw new \InvalidArgumentException("tag_id {$tag_id} isn't unique and cannot be updated, only added or deleted");
    //}
    if ($type == 'i' || $type == 'c') {
      $tag_value = $this->sanitize_int($tag_value);
    }
    if ($type == 'n') {
      throw new \InvalidArgumentException("Tag {$tag_id} cannot be updated as it has no data attached");
    }
    if ($type == 'j') {
      $tag_value = json_encode($tag_value, JSON_FORCE_OBJECT);
      if ($tag_value === false) {
        throw new \InvalidArgumentException("JSON tag could {$tag_id} not be converted to JSON string");
      }
    }

    if ($this->isUniquetag($tag_id)) {
      if ($this->hasTagId($tag_id)) {
        return $this->doUpdateTag($tag_id, $tag_value);
      } else {
        return $this->doAddTag($tag_id, $tag_value);
      }
    }

    // Not a unique tag, so we have to search by both tag_id AND the tag_value
    if ($this->hasTagIdValue($tag_id, $tag_value)) {
      return $this->doUpdateTagValue($tag_id, $tag_value);
    } else {
      return $this->doAddTag($tag_id, $tag_value);
    }
  }


  /**
   * @param int $tag_id
   * @param string|null $tag_value
   * @return mixed
   */
  public function deleteTagIdValue($tag_id, $tag_value)
  {
    $type = $this->checkTag($tag_id);
    $tag_id = $this->sanitize_int($tag_id);

    return $this->doDeleteTagIdValue($tag_id, $this->convertToType($tag_value, $type));
  }


  /**
   * @param int $tag_id
   * @return mixed
   */
  public function deleteTag($tag_id)
  {
    $this->checkTag($tag_id, null, 1);
    $tag_id = $this->sanitize_int($tag_id);

    return $this->doDeleteTag($tag_id);
  }


  /**
   * @return mixed
   */
  public function deleteALLTags()
  {
    return $this->doDeleteAllTags();
  }


  /**
   * @return array
   */
  public function getTypes()
  {
    return $this->doGetTypes();
  }


  /**
   * @param mixed $value
   * @param string $type
   * @return int|mixed|null
   */
  protected function convertToType($value, $type)
  {
    if ($type == 'c' || $type == 'i') {
      return $this->sanitize_int($value);
    }
    if ($type == 'n') {
      return null;
    }
    if ($type == 'j') {
      return json_decode($value, true);
    }

    return $value;
  }


  /**
   * Sanitizes an integer value.
   *
   * Note that intval returns 1 for an array or an object. We therefore check for an array and ensure we return 0
   * in those special cases, and in all other cases we return the value from the intval function
   *
   * @param mixed $tag_id
   * @return int
   */
  protected function sanitize_int($tag_id)
  {
    return is_array($tag_id) || is_object($tag_id) ? 0 : intval($tag_id);
  }


  /**
   * Retrieves all the different tags registered in the system in a multi-dimensional array
   * Each array element must have: tag_id, tag_type
   *
   * Valid values for tag_type:
   *   n - no tag data attached
   *   i - integer data
   *   t - text data
   *   j - json formatted data; will be returned as an array to the caller
   *   c - counter - can be set, reset, incremented and decremented
   *
   * Ideally this data should be cached at least locally
   *
   * @return array
   */
  abstract protected function doGetTypes();


  /**
   * Retrieves a single tag (must be a unique tag) for a specific record in the system, in a single-dimensional array
   *
   * @param $tag_id
   * @return array
   */
  abstract protected function doGetTagById($tag_id);


  /**
   * Retrieves all the different tag values for a specific tag_id for a specific record in the system, in a multi-dimensional array
   *
   * @param $tag_id
   * @return array
   */
  abstract protected function doGetTags($tag_id);


  /**
   * Retrieves all the different tag values for a specific tag_id for a specific record in the system, in a multi-dimensional array
   *
   * @param int $tag_id
   * @param $tag_value
   * @return array
   */
  abstract protected function doGetTagByIdValue($tag_id, $tag_value);


  /**
   * Returns true if a tag_id/value pair exists
   *
   * @param int $tag_id
   * @param int $value
   * @return boolean
   */
  abstract protected function doHasTagIdValue($tag_id, $value);


  /**
   * Returns true if a tag_id exists, regardless of how many or values
   *
   * @param int $tag_id
   * @return boolean
   */
  abstract protected function doHasTagId($tag_id);


  /**
   * Implements retrieval of all tags
   */
  abstract protected function doGetAllTags();


  /**
   * @param int $tag_id
   * @param string|null $tag_value
   * @return mixed
   */
  abstract protected function doAddTag($tag_id, $tag_value);


  /**
   * @param int $tag_id
   * @param string|null $tag_value
   * @return mixed
   */
  abstract protected function doUpdateTag($tag_id, $tag_value);


  /**
   * This will update a value to the same value - useless. Unless you want to change a timestamp
   * or otherwise take an action because the value is being updated. SO let's include a call to it.
   * In your implementation you can leave it empty if you don't care about a "refresh" of already
   * stored data.
   *
   * @param int $tag_id
   * @param string|null $tag_value
   * @return mixed
   */
  abstract protected function doUpdateTagValue($tag_id, $tag_value);


  /**
   * @param int $tag_id
   * @param string|null $tag_value
   * @return mixed
   */
  abstract protected function doDeleteTagIdValue($tag_id, $tag_value);


  /**
   * @param int $tag_id
   * @return mixed
   */
  abstract protected function doDeleteTag($tag_id);


  /**
   * @return mixed
   */
  abstract protected function doDeleteAllTags();


}
