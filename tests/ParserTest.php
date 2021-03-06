<?php

namespace Galahad\Bbcode\Tests;

use Galahad\Bbcode\Exception\MissingAttributeException;
use Galahad\Bbcode\Exception\MissingTagException;
use Galahad\Bbcode\Parser;
use Illuminate\Support\Arr;
use PHPUnit\Framework\TestCase;

/**
 * Class ParserTest
 *
 * @package Galahad\Bbcode\Tests
 * @author Junior Grossi <juniorgro@gmail.com>
 */
class ParserTest extends TestCase
{
    /**
     * @test
     */
    public function parseBIUSTags()
    {
        $options = ['b' => 'strong', 'i' => 'em', 'u' => 'u', 's' => 's'];

        foreach ($options as $tag => $newTag) {
            $input = "[$tag]foo bar[/$tag]";
            $output = "<$newTag>foo bar</$newTag>";
            $this->assertEquals($output, $this->parser()->parse($input));
        }
    }

    /**
     * @test
     */
    public function parseColor()
    {
        $colors = ['red', '#ff0000'];

        foreach ($colors as $color) {
            $text = "this is a [color=$color]colored[/color] word";
            $this->assertEquals(
                "this is a <span style=\"color: $color;\">colored</span> word",
                $this->parser()->parse($text)
            );
        }

        // Missing color attribute
        $this->expectException(MissingAttributeException::class);
        $this->parser()->parse('[color]text[/color]');
    }

    /**
     * @test
     */
    public function parseSize()
    {
        $tests = [
            '1' => '60%', '2' => '89%', '3' => '100%', '4' => '120%', '5' => '150%',
            '6' => '200%', '7' => '300%', '+1' => '120%', '+2' => '150%', '-1' => '89%',
            '-2' => '60%', 'medium' => 'medium', 'small' => 'small', 'x-large' => 'x-large',
        ];

        foreach ($tests as $size => $expected) {
            $this->assertEquals(
                "<span style=\"font-size: $expected;\">This is awesome</span>",
                $this->parser()->parse("[size=$size]This is awesome[/size]")
            );
        }

        // Missing size attribute
        $this->expectException(MissingAttributeException::class);
        $this->parser()->parse('[size]text[/size]');
    }

    /**
     * @test
     */
    public function parseFont()
    {
        $this->assertEquals(
            '<span style="font-family: Times New Roman;">Testing</span>',
            $this->parser()->parse('[font=Times New Roman]Testing[/font]')
        );

        // Missing font attribute
        $this->expectException(MissingAttributeException::class);
        $this->parser()->parse('[font]text[/font]');
    }

    /**
     * @test
     */
    public function parseHighlight()
    {
        $this->assertEquals(
            'this is a <mark>test text</mark>',
            $this->parser()->parse('this is a [highlight]test text[/highlight]')
        );
    }

    /**
     * @test
     */
    public function parseTextAlignment()
    {
        $text = 'just a test text';
        $positions = ['left', 'center', 'right'];

        foreach ($positions as $position) {
            $this->assertEquals(
                "<div style=\"text-align: $position;\">$text</div>",
                $this->parser()->parse("[{$position}]{$text}[/{$position}]")
            );
        }

        $this->expectException(MissingAttributeException::class);
        $this->parser()->parse('[font]text[/font]');
    }

    /**
     * @test
     */
    public function parseIndent()
    {
        $this->assertEquals(
            '<blockquote><div>this text is indented</div></blockquote>',
            $this->parser()->parse('[indent]this text is indented[/indent]')
        );
    }

    /**
     * @test
     */
    public function parseEmail()
    {
        $this->assertEquals(
            '<a href="mailto:foo@bar.com">foo@bar.com</a>',
            $this->parser()->parse('[email]foo@bar.com[/email]')
        );

        $this->assertEquals(
            '<a href="mailto:foo@bar.com">click me</a>',
            $this->parser()->parse('[email=foo@bar.com]click me[/email]')
        );
    }

    /**
     * @test
     */
    public function parseUrl()
    {
        $urls = ['http://foo.com', 'http://bar.com?foo=bar%20&foo#bar'];

        foreach ($urls as $url) {
            $this->assertEquals(
                "<a href=\"$url\" target=\"_blank\">$url</a>",
                $this->parser()->parse("[url]{$url}[/url]")
            );

            $this->assertEquals(
                "<a href=\"$url\" target=\"_blank\">foo url</a>",
                $this->parser()->parse("[url={$url}]foo url[/url]")
            );
        }

        $this->assertEquals(
            '<a href="http://foo.bar" target="_blank">foo.bar</a>',
            $this->parser()->parse('[url="http://foo.bar"]foo.bar[/url]')
        );
    }

    /**
     * @test
     */
    public function parseThread()
    {
        $url = 'http://example.com/thread/42918/bar';

        $input = '[thread=42918]Click Me![/thread]';
        $output = sprintf('<a href="%s">Click Me!</a>', $url);
        $this->assertEquals($output, $this->parser()->parse($input));

        $input = '[thread]42918[/thread]';
        $output = sprintf('<a href="%1$s">%1$s</a>', $url);
        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parsePost()
    {
        $url = 'http://example.com/posts/269302';

        $input = '[post=269302]Click Me![/post]';
        $output = sprintf('<a href="%s">Click Me!</a>', $url);
        $this->assertEquals($output, $this->parser()->parse($input));

        $input = '[post]269302[/post]';
        $output = sprintf('<a href="%1$s">%1$s</a>', $url);
        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseBulletList()
    {
        $list =<<<TEXT
[list] 
[*] list item 1
[*] list item 2
[/list]
TEXT;

        $this->assertEquals(
            '<ul><li>list item 1</li><li>list item 2</li></ul>',
            $this->parser()->parse($list)
        );
    }

    /**
     * @test
     */
    public function parseAdvancedList()
    {
        $input = <<<INPUT
[list=1]
[*]list item 1
[*]list item 2
[/list]
INPUT;

        $output = '<ol type="1"><li>list item 1</li><li>list item 2</li></ol>';

        $this->assertEquals($output, $this->parser()->parse($input));

        $input = <<<INPUT
[list=a]
[*]list item 1
[*]list item 2
[/list]
INPUT;

        $output = '<ol type="a"><li>list item 1</li><li>list item 2</li></ol>';

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseImg()
    {
        $input = '[img]http://example.com/assets/image.gif[/img]';
        $output = '<img class="" src="http://example.com/assets/image.gif"/>';

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseCode()
    {
        $input = <<<INPUT
[code]
<script type="text/javascript">
<!--
    alert("Hello world!");
//-->
</script>
[/code]
INPUT;

        $output = <<<OUTPUT
<code><pre>
<script type="text/javascript">
<!--
    alert("Hello world!");
//-->
</script>
</pre></code>
OUTPUT;

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parsePhp()
    {
        $input = <<<'INPUT'
[php]
$myvar = 'Hello World!';
for ($i = 0; $i < 10; $i++)
{
    echo $myvar . "\n";
}
[/php]
INPUT;

        $output = <<<'OUTPUT'
<code><pre>
$myvar = 'Hello World!';
for ($i = 0; $i < 10; $i++)
{
    echo $myvar . "\n";
}
</pre></code>
OUTPUT;

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseHtml()
    {
        $input = <<<INPUT
[html]
<img src="image.gif" alt="image" />
<a href="testing.html" target="_blank">Testing</a>
[/html]
INPUT;

        $output = <<<OUTPUT
<code><pre>
<img src="image.gif" alt="image" />
<a href="testing.html" target="_blank">Testing</a>
</pre></code>
OUTPUT;

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseQuote()
    {
        $input = '[quote]Lorem ipsum dolor sit amet[/quote]';
        $output = <<<OUTPUT
<blockquote>
    <p>Lorem ipsum dolor sit amet</p>
    <footer></footer>
</blockquote>
OUTPUT;

        $this->assertEquals($output, $this->parser()->parse($input));

        $input = '[quote=John Doe]Lorem ipsum dolor sit amet[/quote]';
        $output = <<<OUTPUT
<blockquote>
    <p>Lorem ipsum dolor sit amet</p>
    <footer>John Doe</footer>
</blockquote>
OUTPUT;

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseQuoteWithLinkToPost()
    {
        $input = '[quote=John Doe;12345]Lorem ipsum dolor sit amet[/quote]';
        $url = 'http://example.com/posts/12345';
        $output = <<<OUTPUT
<blockquote>
    <p>Lorem ipsum dolor sit amet</p>
    <footer><a href="$url">John Doe</a></footer>
</blockquote>
OUTPUT;

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseNoparse()
    {
        $input = '[noparse][b]Lorem ipsum dolor sit amet[/b][/noparse]';
        $output = '[b]Lorem ipsum dolor sit amet[/b]';

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseAttach()
    {
        $input = '[attach]12345[/attach]';
        $url = 'http://example.com/attach/12345';
        $output = sprintf('<a href="%1$s">%1$s</a>', $url);

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseA()
    {
        $input = '[a=#foo]Anchor Text[/a]';
        $output = '<a href="#foo">Anchor Text</a>';

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseAlign()
    {
        $input = '[align=center]text alignment[/align]';
        $output = '<div style="text-align: center;">text alignment</div>';

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseFloatRight()
    {
        $input = '[floatright]This text will float right.[/floatright]';
        $output = '<div style="float: right;">This text will float right.</div>';

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseHeaders()
    {
        $input = '[h2]Header[/h2]';
        $output = '<h2>Header</h2>';
        $this->assertEquals($output, $this->parser()->parse($input));

        $input = '[h3]Header[/h3]';
        $output = '<h3>Header</h3>';
        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseHigh()
    {
        $input = '[high]This text is highlighted[/high]';
        $output = '<mark>This text is highlighted</mark>';

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseHr()
    {
        $input = '[hr]The text goes here.[/hr]';
        $output = '<hr />The text goes here.<hr />';

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseImageLeftAndRight()
    {
        $options = ['imglft' => 'left', 'imgrft' => 'right'];

        foreach ($options as $tag => $position) {
            $input = "[{$tag}]http://example.com/foo.jpg[/{$tag}]";
            $output = <<<OUTPUT
<img src="http://example.com/foo.jpg" alt="" style="float: $position;">
OUTPUT;
            $this->assertEquals($output, $this->parser()->parse($input));
        }
    }

    /**
     * @test
     */
    public function parseJira()
    {
        $input = '[jira]vbiv-123[/jira]';
        $url = 'http://tracker.vbulletin.com/browse/vbiv-123';
        $output = sprintf('<a href="%s">%s</a>', $url, 'VBIV-123');

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseLft()
    {
        $input = '[lft]This is a text.[/lft]';
        $output = '<div style="float: left;">This is a text.</div>';

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseName()
    {
        $input = '[name]Xenon[/name]';
        $url = 'http://example.com/users/show/xenon';
        $output = sprintf('<a href="%s">%s</a>', $url, 'Xenon');

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseNote()
    {
        $input = '[note]Foo bar.[/note]';
        $output = '<div class="alert alert-info">Foo bar.</div>';

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parsePre()
    {
        $input = '[pre]Foo bar.[/pre]';
        $output = '<pre>Foo bar.</pre>';

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseProcess()
    {
        $input = '[process]Foo > Bar[/process]';
        $output = '<ol class="breadcrumb"><li><a>Foo</a></li><li class="active">Bar</li></ol>';

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseRft()
    {
        $input = '[rft]This is a text.[/rft]';
        $output = '<div style="float: right;">This is a text.</div>';

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseWarning()
    {
        $input = '[warning]Foo bar.[/warning]';
        $output = '<div class="alert alert-warning">Foo bar.</div>';

        $this->assertEquals($output, $this->parser()->parse($input));
    }

    /**
     * @test
     */
    public function parseCustom()
    {
        $input = '[jgrossi repo="foo"]Junior Grossi[/jgrossi]';
        $output = '<a href="http://github.com/jgrossi/foo">Junior Grossi</a>';
        $this->assertEquals($output, $this->customParser()->parse($input));

        $input = '[jgrossi repo="bar"][b]Junior Grossi[/b][/jgrossi]';
        $output = '<a href="http://github.com/jgrossi/bar"><strong>Junior Grossi</strong></a>';
        $this->assertEquals($output, $this->customParser()->parse($input));
    }

    /**
     * @test
     */
    public function parseCustomUsingClassName()
    {
        $input = '[foo]bar[/foo]';
        $output = '<a href="#foo">bar</a>';

        $this->assertEquals($output, $this->customParser()->parse($input));
    }

    /**
     * @test
     */
    public function missingTagException()
    {
        $this->expectException(MissingTagException::class);

        $parser = new Parser();
        $parser->parse('Text [fake]to fail[/fake]');
    }

    /**
     * @return Parser
     */
    private function parser()
    {
        return new Parser([
            'thread_url' => 'http://example.com/thread/{thread_id}/bar',
            'post_url' => 'http://example.com/posts/{post_id}',
            'attach_url' => 'http://example.com/attach/{attach_id}',
            'jira_url' => 'http://tracker.vbulletin.com/browse/{jira_id}',
            'user_url' => 'http://example.com/users/show/{user_id}',
        ]);
    }

    /**
     * @return Parser
     */
    private function customParser()
    {
        $parser = $this->parser();

        $parser->extend('jgrossi', function ($block, $attributes, $content) {
            $repo = Arr::get($attributes, 'repo');
            $url = 'http://github.com/jgrossi/'.strtolower($repo);

            return sprintf('<a href="%s">%s</a>', $url, $content);
        });

        $parser->extend('foo', FooTag::class);

        return $parser;
    }
}
