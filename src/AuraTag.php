<?php

namespace memdevs\tag;


use Aura\Sql\ExtendedPdo;
use Carbon\Carbon;


/**
 *
 *
 */
class AuraTag extends AbstractTag
{

  /** @var int */
  protected $external_id;

  /** @var \Aura\Sql\ExtendedPdo */
  protected $pdo;

  /** @var string */
  protected $table_prefix = '';

  /** @var \Aura\Sql\ExtendedPdo */
  static $default_pdo;

  /** @var string */
  static $default_table_prefix = '';

  protected $TZ = 'America/New_York';


  /**
   * @param int $external_id The external ID
   * @param \Aura\Sql\ExtendedPdo $pdo Optional: a PDO connection object
   * @param string $table_prefix
   */
  public function __construct($external_id = null, $pdo = null, $table_prefix = null)
  {
    $this->setExternalId($external_id);
    $this->setPDO($pdo instanceof ExtendedPdo ? $pdo : static::$default_pdo);
    $this->setTablePrefix($table_prefix);
  }


  /**
   * @return \Aura\Sql\ExtendedPdo
   */
  public static function getDefaultPdo()
  {
    return static::$default_pdo;
  }


  /**
   * @param \Aura\Sql\ExtendedPdo $default_pdo
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
    if (is_null($prefix)) {
      $this->table_prefix = static::$default_table_prefix;
    } else {
      $this->table_prefix = $prefix;
    }

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
   * Set the PDO connection - in this case Aura's SQL library
   *
   * @param \Aura\Sql\ExtendedPdo $pdo
   * @return $this
   */
  public function setPDO(\Aura\Sql\ExtendedPdo $pdo)
  {
    if (is_null($pdo)) {
      $this->pdo = static::$default_pdo;
    } else {
      $this->pdo = $pdo;
    }

    return $this;
  }


  /**
   * @return ExtendedPdo
   */
  public function getPdo()
  {
    return $this->pdo;
  }


  /**
   * @return string
   */
  public function getTZ()
  {
    return $this->TZ;
  }


  /**
   * @param string $TZ
   * @return AuraTag
   */
  public function setTZ($TZ)
  {
    $this->TZ = $TZ;

    return $this;
  }


  /**
   * @return $this
   */
  public function truncate()
  {
    $sql = "TRUNCATE TABLE {$this->table_prefix}tagentry";
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
      if ($timestamp > Carbon::now(self::getTZ())->getTimestamp()) {
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

    return $this->updateTag($tag_id, Carbon::now(self::getTZ())->addSeconds($seconds)->getTimestamp());
  }


  /**
   * {@inheritdoc}
   */
  protected function doGetTagById($tag_id)
  {
    $sql = "SELECT * FROM {$this->table_prefix}tagentry WHERE external_id = :external_id AND tag_id = :tag_id ORDER BY tagentry_id LIMIT 1";
    $bind = ['external_id' => $this->external_id, 'tag_id' => $tag_id];

    return $this->pdo->fetchOne($sql, $bind);
  }


  /**
   * {@inheritdoc}
   */
  protected function doGetTagByIdValue($tag_id, $value)
  {
    $sql = "SELECT * FROM {$this->table_prefix}tagentry WHERE external_id = :external_id AND tag_id = :tag_id AND tag_value = :value";
    $bind = ['external_id' => $this->external_id, 'tag_id' => $tag_id, 'value' => $value];

    return $this->pdo->fetchOne($sql, $bind);
  }


  /**
   * {@inheritdoc}
   */
  protected function doGetTags($tag_id)
  {
    $sql = "SELECT * FROM {$this->table_prefix}tagentry WHERE external_id = :external_id AND tag_id = :tag_id ORDER BY tagentry_id";
    $bind = ['external_id' => $this->external_id, 'tag_id' => $tag_id];

    return $this->pdo->fetchAssoc($sql, $bind);
  }


  /**
   * {@inheritdoc}
   */
  protected function doGetAllTags()
  {
    $sql = "SELECT * FROM {$this->table_prefix}tagentry WHERE external_id = :external_id ORDER BY tagentry_id";
    $bind = ['external_id' => $this->external_id];

    return $this->pdo->fetchAssoc($sql, $bind);
  }


  /**
   * {@inheritdoc}
   */
  protected function doHasTagId($tag_id)
  {
    $sql = "SELECT COUNT(*) FROM {$this->table_prefix}tagentry WHERE external_id = :external_id AND tag_id = :tag_id";
    $bind = ['external_id' => $this->external_id, 'tag_id' => $tag_id];

    return (bool)$this->pdo->fetchValue($sql, $bind);
  }


  /**
   * {@inheritdoc}
   */
  protected function doHasTagIdValue($tag_id, $value)
  {
    $sql = "SELECT DISTINCT 1 FROM {$this->table_prefix}tagentry WHERE external_id = :external_id AND tag_id = :tag_id AND tag_value = :value";
    $bind = ['external_id' => $this->external_id, 'tag_id' => $tag_id, 'value' => $value];

    return (bool)$this->pdo->fetchValue($sql, $bind);
  }


  /**
   * {@inheritdoc}
   */
  protected function doAddTag($tag_id, $value)
  {
    $timestamp = Carbon::now(self::getTZ())->format('Y-m-d H:i:s');
    $sql = "INSERT INTO {$this->table_prefix}tagentry (external_id, tag_id, tag_value, created_at, updated_at) VALUES (:external_id, :tag_id, :tag_value, :created_at, :updated_at)";
    $bind = [
      'external_id' => $this->external_id,
      'tag_id' => $tag_id,
      'tag_value' => $value,
      'created_at' => $timestamp,
      'updated_at' => $timestamp,
    ];
    //return $this->pdo->perform($sql, $bind)->rowCount();
    $this->pdo->perform($sql, $bind);

    return $this;
  }


  /**
   * {@inheritdoc}
   */
  protected function doUpdateTag($tag_id, $value)
  {
    $timestamp = Carbon::now(self::getTZ())->format('Y-m-d H:i:s');

    $sql = "UPDATE {$this->table_prefix}tagentry SET tag_value = :tag_value, updated_at = :updated_at WHERE external_id = :external_id AND tag_id = :tag_id LIMIT 1";
    $bind = [
      'external_id' => $this->external_id,
      'tag_id' => $tag_id,
      'tag_value' => $value,
      'updated_at' => $timestamp,
    ];

    return $this->pdo->perform($sql, $bind)->rowCount();
  }


  /**
   * {@inheritdoc}
   */
  protected function doUpdateTagValue($tag_id, $value)
  {
    $timestamp = Carbon::now(self::getTZ())->format('Y-m-d H:i:s');

    $sql = "UPDATE {$this->table_prefix}tagentry SET updated_at = :updated_at WHERE external_id = :external_id AND tag_id = :tag_id AND tag_value = :tag_value LIMIT 1";
    $bind = [
      'external_id' => $this->external_id,
      'tag_id' => $tag_id,
      'tag_value' => $value,
      'updated_at' => $timestamp,
    ];

    return $this->pdo->perform($sql, $bind)->rowCount();
  }


  /**
   * {@inheritdoc}
   */
  protected function doDeleteTagIdValue($tag_id, $value)
  {
    $sql = "DELETE FROM {$this->table_prefix}tagentry WHERE external_id = :external_id AND tag_id = :tag_id AND tag_value = :value";
    $bind = ['external_id' => $this->external_id, 'tag_id' => $tag_id, 'value' => $value];

    return (bool)$this->pdo->perform($sql, $bind)->rowCount();
  }


  /**
   * {@inheritdoc}
   */
  protected function doDeleteTag($tag_id)
  {
    $sql = "DELETE FROM {$this->table_prefix}tagentry WHERE external_id = :external_id AND tag_id = :tag_id";
    $bind = ['external_id' => $this->external_id, 'tag_id' => $tag_id];

    return (bool)$this->pdo->perform($sql, $bind)->rowCount();
  }


  /**
   * {@inheritdoc}
   */
  protected function doDeleteAllTags()
  {
    $sql = "DELETE FROM {$this->table_prefix}tagentry WHERE external_id = :external_id";
    $bind = [
      'external_id' => $this->external_id,
    ];

    return $this->pdo->perform($sql, $bind)->rowCount();
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

    $sql = "SELECT tag_id, tag_name, tag_type, tag_is_unique FROM {$this->table_prefix}tagdefinition ORDER BY tag_id";
    $types[$local_cache_key] = $this->pdo->fetchAssoc($sql);

    return $types[$local_cache_key];
  }


}
