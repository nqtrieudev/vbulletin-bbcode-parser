<?php

namespace Galahad\Bbcode;

use Galahad\Bbcode\Exception\MissingAttributeException;
use Galahad\Bbcode\Exception\MissingTagException;
use Galahad\Bbcode\Tags\BulletList;
use Illuminate\Support\Arr;

/**
 * Class Tag
 *
 * @package Galahad\Bbcode
 * @author Junior Grossi <juniorgro@gmail.com>
 */
class Tag
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $block;

    /**
     * @var mixed
     */
    protected $content;

    /**
     * @var array
     */
    protected $attributes;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function tagColor()
    {
        $this->validateAttribute();

        return sprintf(
            '<span style="color: %s;">%s</span>',
            Arr::first($this->attributes),
            $this->content
        );
    }

    /**
     * @return string
     * @see http://style.cleverchimp.com/font_size_intervals/altintervals.html#bbs
     */
    public function tagSize()
    {
        $this->validateAttribute();

        $attribute = Arr::first($this->attributes);
        $scale = [60, 89, 100, 120, 150, 200, 300];
        $position = intval($attribute) - 1; // -1 -1 or +1 - 1

        if (in_array($attribute[0], ['-', '+'])) {
            $position = 3 + $position;
        }

        return sprintf(
            '<span style="font-size: %s;">%s</span>',
            isset($scale[$position]) ? $scale[$position] . '%' : $attribute,
            $this->content
        );
    }

    /**
     * @return string
     */
    public function tagFont()
    {
        $this->validateAttribute();

        return sprintf(
            '<span style="font-family: %s;">%s</span>',
            Arr::first($this->attributes),
            $this->content
        );
    }

    /**
     * @return string
     */
    public function tagHighlight()
    {
        return sprintf(
            '<mark>%s</mark>',
            $this->content
        );
    }

    /**
     * @param string $position
     * @return string
     */
    protected function renderTextAlignment($position = 'left')
    {
        return sprintf(
            '<div style="text-align: %s;">%s</div>',
            $position,
            $this->content
        );
    }

    /**
     * @return string
     */
    public function tagLeft()
    {
        return $this->renderTextAlignment('left');
    }

    /**
     * @return string
     */
    public function tagCenter()
    {
        return $this->renderTextAlignment('center');
    }

    /**
     * @return string
     */
    public function tagRight()
    {
        return $this->renderTextAlignment('right');
    }

    /**
     * @return string
     */
    public function tagIndent()
    {
        return sprintf(
            '<blockquote><div>%s</div></blockquote>',
            $this->content
        );
    }

    /**
     * @return string
     */
    public function tagEmail()
    {
        $attribute = Arr::first($this->attributes);

        return sprintf(
            '<a href="mailto:%s">%s</a>',
            $attribute ?: $this->content,
            $this->content
        );
    }

    /**
     * @return string
     */
    public function tagUrl()
    {
        $attribute = Arr::first($this->attributes);

        return sprintf(
            '<a href="%s" target="_blank">%s</a>',
            $attribute ?: $this->content,
            $this->content
        );
    }

    /**
     * @return string
     */
    public function tagThread()
    {
        // TODO
    }

    /**
     * @return string
     */
    public function tagPost()
    {
        // TODO
    }

    /**
     * @param $text
     * @return string
     */
    public function tagList($text)
    {
        $renderer = new BulletList();

        return $renderer->render($text);
    }

    /**
     * @throws MissingAttributeException
     */
    protected function validateAttribute()
    {
        if (empty(trim(Arr::first($this->attributes)))) {
            throw new MissingAttributeException();
        }
    }

    /**
     * @param string $text
     * @return string
     * @throws MissingTagException
     */
    public function render($text)
    {
        $method = 'tag'.ucfirst($this->name);

        if (method_exists($this, $method)) {
            $this->split($text);
            $html = $this->$method($text);

            return str_replace($this->block, $html, $text);
        }

        throw new MissingTagException("Missing parser for $this->name tag");
    }

    /**
     * @param string $string
     * @return array
     */
    protected function splitAttributes($string)
    {
        $pattern = '/([\w\d]+)=("([^ ]+)"|[^"\]]+)/i';
        preg_match_all($pattern, $string, $matches, PREG_SET_ORDER);
        $attributes = [];

        foreach ($matches as $match) {
            $key = Arr::get($match, 1);
            $attributes[$key] = Arr::last($match);
        }

        return $attributes;
    }

    /**
     * @param string $text
     * @internal param string $tag
     */
    protected function split($text)
    {
        $pattern = '/\[(%s.*?)\](.+?)\[\/%s\]/is';
        $pattern = sprintf($pattern, $this->name, $this->name);

        preg_match($pattern, $text, $match);

        list($this->block, $attributes, $this->content) = $match;
        $this->attributes = $this->splitAttributes($attributes);
    }
}
