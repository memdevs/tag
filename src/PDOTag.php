<?php

namespace memdevs\tag;


/**
 * Uses the PDO tag to implement tagging. No external dependencies
 */
class PDOTag extends AbstractTag
{

  /** @var int */
  protected $external_id;

  /** @var \PDO */
  protected $pdo;

  /** @var \PDO */
  static $default_pdo;

  /** @var string */
  protected $table_prefix = '';

  protected $table_name = 'tagentry';
  protected $table_definition_name = 'tagdefinition';

  protected $is_timestamp_enabled = false;

  /** @var string */
  static $default_table_prefix = '';


  /**
   * @param int $external_id The external ID
   * @param \PDO $pdo Optional: a PDO connection object
   * @param string $table_prefix
   */
  public function __construct($external_id = null, $pdo = null, $table_prefix = null)
  {
    $this->setExternalId($external_id);
    $this->setPDO($pdo);
    $this->setTablePrefix($table_prefix);
  }


  /**
   * @return \PDO
   */
  public static function getDefaultPdo()
  {
    return static::$default_pdo;
  }


  /**
   * @param \PDO $default_pdo
   */
  public static function setDefaultPdo($default_pdo)
  {
    static::$default_pdo = $default_pdo;
  }


  /**
   * @return string
   */
  public static function getDefaultTablePrefix()
  {
    return static::$default_table_prefix;
  }


  /**
   * @param string $default_table_prefix
   */
  public static function setDefaultTablePrefix($default_table_prefix)
  {
    static::$default_table_prefix = $default_table_prefix;
  }


  /**
   * @param int $external_id
   * @return $this
   */
  public function setExternalId($external_id)
  {
    $this->external_id = $external_id;

    return $this;
  }


  /**
   * @return int
   */
  public function getExternalId()
  {
    return $this->external_id;
  }


  /**
   * @param string $prefix Ensure this is a sanitized value
   * @return $this
   */
  public function setTablePrefix($prefix)
  {
    $this->table_prefix = $prefix ?: static::$default_table_prefix;

    return $this;
  }


  /**
   * @return string
   */
  public function getTablePrefix()
  {
    return $this->table_prefix;
  }


  /**
   * @return string
   */
  public function getTableName()
  {
    return $this->table_name;
  }


  /**
   * @param string $table_name
   * @return PDOTag
   */
  public function setTableName($table_name)
  {
    $this->table_name = $table_name;

    return $this;
  }


  /**
   * @return string
   */
  public function getTableDefinitionName()
  {
    return $this->table_definition_name;
  }


  /**
   * @param string $table_definition_name
   * @return PDOTag
   */
  public function setTableDefinitionName($table_definition_name)
  {
    $this->table_definition_name = $table_definition_name;

    return $this;
  }


  /**
   * @return bool
   */
  public function isTimestampEnabled()
  {
    return $this->is_timestamp_enabled;
  }


  /**
   * @param bool $is_timestamp_enabled
   * @return PDOTag
   */
  public function setTimestampEnabled($is_timestamp_enabled = true)
  {
    $this->is_timestamp_enabled = $is_timestamp_enabled;

    return $this;
  }


  /**
   * Set the PDO connection - in this case PDO library
   *
   * @param \PDO $pdo
   * @return $this
   */
  public function setPDO(\PDO $pdo)
  {
    $this->pdo = $pdo ?: static::$default_pdo;

    return $this;
  }


  /**
   * @return \PDO
   */
  public function getPdo()
  {
    return $this->pdo;
  }


  /**
   * @return $this
   */
  public function truncate()
  {
    $sql = "TRUNCATE TABLE {$this->table_prefix}{$this->table_name}";
    $this->pdo->exec($sql);

    return $this;
  }


  /**
   * Implements a timed tag; won't do this in the abstract class as it does not have the concept of "time"
   *
   * @param int $tag_id
   * @return bool
   */
  public function hasTimedTag($tag_id)
  {
    $this->checkTag($tag_id, 'i', true);
    $tag = $this->getTag($tag_id);
    if ($tag) {
      $timestamp = $tag['tag_value'];
      if ($timestamp > time()) {
        return true;
      }
    }

    return false;
  }


  /**
   * Implements a timed tag; won't do this in the abstract class as it does not have the concept of "time"
   *
   * @param int $tag_id
   * @param $seconds
   * @param bool $allow_overwrite
   * @return mixed
   */
  public function setTimedTag($tag_id, $seconds, $allow_overwrite = true)
  {
    $this->checkTag($tag_id, 'i', true);
    if (!$allow_overwrite) {
      if ($this->hasTimedTag($tag_id)) {
        return $this;
      }
    }

    return $this->updateTag($tag_id, time() + $seconds);
  }


  /**
   * {@inheritdoc}
   */
  protected function doGetTagById($tag_id)
  {
    $sql = "SELECT * FROM {$this->table_prefix}{$this->table_name} WHERE external_id = :external_id AND tag_id = :tag_id ORDER BY {$this->table_name}_id LIMIT 1";
    $bind = ['external_id' => $this->external_id, 'tag_id' => $tag_id];

    return $this->fetchOne($sql, $bind);
  }


  /**
   * {@inheritdoc}
   */
  protected function doGetTagByIdValue($tag_id, $value)
  {
    $sql = "SELECT * FROM {$this->table_prefix}{$this->table_name} WHERE external_id = :external_id AND tag_id = :tag_id AND tag_value = :value";
    $bind = ['external_id' => $this->external_id, 'tag_id' => $tag_id, 'value' => $value];

    return $this->fetchOne($sql, $bind);
  }


  /**
   * {@inheritdoc}
   */
  protected function doGetTags($tag_id)
  {
    $sql = "SELECT * FROM {$this->table_prefix}{$this->table_name} WHERE external_id = :external_id AND tag_id = :tag_id ORDER BY {$this->table_name}_id";
    $bind = ['external_id' => $this->external_id, 'tag_id' => $tag_id];

    return $this->fetchAssoc($sql, $bind);
  }


  /**
   * {@inheritdoc}
   */
  protected function doGetAllTags()
  {
    $sql = "SELECT * FROM {$this->table_prefix}{$this->table_name} WHERE external_id = :external_id ORDER BY {$this->table_name}_id";
    $bind = ['external_id' => $this->external_id];

    return $this->fetchAssoc($sql, $bind);
  }


  /**
   * {@inheritdoc}
   */
  protected function doHasTagId($tag_id)
  {
    $sql = "SELECT COUNT(*) FROM {$this->table_prefix}{$this->table_name} WHERE external_id = :external_id AND tag_id = :tag_id";
    $bind = ['external_id' => $this->external_id, 'tag_id' => $tag_id];

    return (bool)$this->fetchValue($sql, $bind);
  }


  /**
   * {@inheritdoc}
   */
  protected function doHasTagIdValue($tag_id, $value)
  {
    $sql = "SELECT DISTINCT 1 FROM {$this->table_prefix}{$this->table_name} WHERE external_id = :external_id AND tag_id = :tag_id AND tag_value = :value";
    $bind = ['external_id' => $this->external_id, 'tag_id' => $tag_id, 'value' => $value];

    return (bool)$this->fetchValue($sql, $bind);
  }


  /**
   * {@inheritdoc}
   */
  protected function doAddTag($tag_id, $value)
  {
    $timestamp = date('Y-m-d H:i:s');
    $bind = [
      'external_id' => $this->external_id,
      'tag_id' => $tag_id,
      'tag_value' => $value,
    ];
    if ($this->isTimestampEnabled()) {
      $sql = "INSERT INTO {$this->table_prefix}{$this->table_name} (external_id, tag_id, tag_value, created_at, updated_at) VALUES (:external_id, :tag_id, :tag_value, :created_at, :updated_at)";
      $bind['created_at'] = $timestamp;
      $bind['updated_at'] = $timestamp;
    } else {
      $sql = "INSERT INTO {$this->table_prefix}{$this->table_name} (external_id, tag_id, tag_value) VALUES (:external_id, :tag_id, :tag_value)";
    }

    $this->perform($sql, $bind);

    return $this;
  }


  /**
   * {@inheritdoc}
   */
  protected function doUpdateTag($tag_id, $value)
  {
    $timestamp = date('Y-m-d H:i:s');

    $bind = [
      'external_id' => $this->external_id,
      'tag_id' => $tag_id,
      'tag_value' => $value,
    ];
    if ($this->isTimestampEnabled()) {
      $sql = "UPDATE {$this->table_prefix}{$this->table_name} SET tag_value = :tag_value, updated_at = :updated_at WHERE external_id = :external_id AND tag_id = :tag_id LIMIT 1";
      $bind['updated_at'] = $timestamp;
    } else {
      $sql = "UPDATE {$this->table_prefix}{$this->table_name} SET tag_value = :tag_value WHERE external_id = :external_id AND tag_id = :tag_id LIMIT 1";
    }

    return $this->perform($sql, $bind);
  }


  /**
   * {@inheritdoc}
   */
  protected function doUpdateTagValue($tag_id, $value)
  {
    $timestamp = date('Y-m-d H:i:s');

    if ($this->isTimestampEnabled()) { // We only care for this update to set a refresh timestamp, that's all.
      $bind = [
        'external_id' => $this->external_id,
        'tag_id' => $tag_id,
        'tag_value' => $value,
        'updated_at' => $timestamp,
      ];
      $sql = "UPDATE {$this->table_prefix}{$this->table_name} SET updated_at = :updated_at WHERE external_id = :external_id AND tag_id = :tag_id AND tag_value = :tag_value LIMIT 1";

      return $this->perform($sql, $bind);
    }

    return 0;
  }


  /**
   * {@inheritdoc}
   */
  protected function doDeleteTagIdValue($tag_id, $value)
  {
    $sql = "DELETE FROM {$this->table_prefix}{$this->table_name} WHERE external_id = :external_id AND tag_id = :tag_id AND tag_value = :value";
    $bind = ['external_id' => $this->external_id, 'tag_id' => $tag_id, 'value' => $value];

    return (bool)$this->perform($sql, $bind);
  }


  /**
   * {@inheritdoc}
   */
  protected function doDeleteTag($tag_id)
  {
    $sql = "DELETE FROM {$this->table_prefix}{$this->table_name} WHERE external_id = :external_id AND tag_id = :tag_id";
    $bind = ['external_id' => $this->external_id, 'tag_id' => $tag_id];

    return (bool)$this->perform($sql, $bind);
  }


  /**
   * {@inheritdoc}
   */
  protected function doDeleteAllTags()
  {
    $sql = "DELETE FROM {$this->table_prefix}{$this->table_name} WHERE external_id = :external_id";
    $bind = [
      'external_id' => $this->external_id,
    ];

    return $this->perform($sql, $bind);
  }


  /**
   * Uses a very simple in-memory cache so we only need to retrieve the data once per call.
   * It's strongly suggested to use a cache to retrieve this information.
   *
   * {@inheritdoc}
   */
  protected function doGetTypes()
  {
    /** @var array */
    static $types;

    $local_cache_key = 'c:' . $this->table_prefix;
    if (is_array($types[$local_cache_key])) {
      return $types[$local_cache_key];
    }

    $sql = "SELECT tag_id, tag_name, tag_type, tag_is_unique FROM {$this->table_prefix}{$this->table_definition_name} ORDER BY tag_id";
    $types[$local_cache_key] = $this->fetchAssoc($sql);

    return $types[$local_cache_key];
  }


  /**
   * @param $sql
   * @return \PDOStatement
   */
  protected function dboStatement($sql)
  {
    return $this->pdo->prepare($sql);
  }


  /**
   * @param string $sql
   * @param array $bind
   * @return array
   */
  protected function fetchOne($sql, $bind = [])
  {
    $statement = $this->dboStatement($sql);
    $statement->execute($bind);

    return $statement->fetch(\PDO::FETCH_ASSOC);
  }


  /**
   * @param string $sql
   * @param array $bind
   * @return int
   */
  protected function perform($sql, $bind = [])
  {
    $statement = $this->dboStatement($sql);
    $statement->execute($bind);

    return $statement->rowCount();
  }


  /**
   * @param string $sql
   * @param array $bind
   * @return mixed
   */
  protected function fetchValue($sql, $bind = [])
  {
    $statement = $this->dboStatement($sql);
    $statement->execute($bind);
    $result = $statement->fetchColumn();

    return isset($result[0]) ? $result[0] : null;
  }


  /**
   * @param string $sql
   * @param array $bind
   * @return array|null
   */
  protected function fetchAssoc($sql, $bind = [])
  {
    $statement = $this->dboStatement($sql);
    $statement->execute($bind);
    $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
    if (isset($result[0]) && is_array($result[0])) {
      $key = key($result[0]);
      $result = array_column($result, null, $key);
    }

    return $result;
  }


}
