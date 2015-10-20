<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @license see /license.txt
 * @author autogenerated
 */
class ForumAttachment extends \CourseEntity
{
    /**
     * @return \Entity\Repository\ForumAttachmentRepository
     */
     public static function repository(){
        return \Entity\Repository\ForumAttachmentRepository::instance();
    }

    /**
     * @return \Entity\ForumAttachment
     */
     public static function create(){
        return new self();
    }

    /**
     * @var integer $c_id
     */
    protected $c_id;

    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $path
     */
    protected $path;

    /**
     * @var text $comment
     */
    protected $comment;

    /**
     * @var integer $size
     */
    protected $size;

    /**
     * @var integer $post_id
     */
    protected $post_id;

    /**
     * @var string $filename
     */
    protected $filename;


    /**
     * Set c_id
     *
     * @param integer $value
     * @return ForumAttachment
     */
    public function set_c_id($value)
    {
        $this->c_id = $value;
        return $this;
    }

    /**
     * Get c_id
     *
     * @return integer 
     */
    public function get_c_id()
    {
        return $this->c_id;
    }

    /**
     * Set id
     *
     * @param integer $value
     * @return ForumAttachment
     */
    public function set_id($value)
    {
        $this->id = $value;
        return $this;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * Set path
     *
     * @param string $value
     * @return ForumAttachment
     */
    public function set_path($value)
    {
        $this->path = $value;
        return $this;
    }

    /**
     * Get path
     *
     * @return string 
     */
    public function get_path()
    {
        return $this->path;
    }

    /**
     * Set comment
     *
     * @param text $value
     * @return ForumAttachment
     */
    public function set_comment($value)
    {
        $this->comment = $value;
        return $this;
    }

    /**
     * Get comment
     *
     * @return text 
     */
    public function get_comment()
    {
        return $this->comment;
    }

    /**
     * Set size
     *
     * @param integer $value
     * @return ForumAttachment
     */
    public function set_size($value)
    {
        $this->size = $value;
        return $this;
    }

    /**
     * Get size
     *
     * @return integer 
     */
    public function get_size()
    {
        return $this->size;
    }

    /**
     * Set post_id
     *
     * @param integer $value
     * @return ForumAttachment
     */
    public function set_post_id($value)
    {
        $this->post_id = $value;
        return $this;
    }

    /**
     * Get post_id
     *
     * @return integer 
     */
    public function get_post_id()
    {
        return $this->post_id;
    }

    /**
     * Set filename
     *
     * @param string $value
     * @return ForumAttachment
     */
    public function set_filename($value)
    {
        $this->filename = $value;
        return $this;
    }

    /**
     * Get filename
     *
     * @return string 
     */
    public function get_filename()
    {
        return $this->filename;
    }
}