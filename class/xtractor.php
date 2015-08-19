<?php
/**
 *
 * @author Simon Roberts <cipher@labs.coop>
 * @subpackage xtdom
 */

/**
 * All of the Defines for the classes below.
 * @author Simon Roberts <cipher@labs.coop>
 */
define('XTDOM_TYPE_ELEMENT', 1);
define('XTDOM_TYPE_COMMENT', 2);
define('XTDOM_TYPE_TEXT',    3);
define('XTDOM_TYPE_ENDTAG',  4);
define('XTDOM_TYPE_ROOT',    5);
define('XTDOM_TYPE_UNKNOWN', 6);
define('XTDOM_QUOTE_DOUBLE', 0);
define('XTDOM_QUOTE_SINGLE', 1);
define('XTDOM_QUOTE_NO',     3);
define('XTDOM_INFO_BEGIN',   0);
define('XTDOM_INFO_END',     1);
define('XTDOM_INFO_QUOTE',   2);
define('XTDOM_INFO_SPACE',   3);
define('XTDOM_INFO_TEXT',    4);
define('XTDOM_INFO_INNER',   5);
define('XTDOM_INFO_OUTER',   6);
define('XTDOM_INFO_ENDSPACE',7);
define('XTDOM_DEFAULT_TARGET_CHARSET', 'UTF-8');
define('XTDOM_DEFAULT_BR_TEXT', "\r\n");
define('XTDOM_DEFAULT_SPAN_TEXT', " ");
define('MAX_FILE_SIZE', 600000);

/**
 * html dom node
 */
class xtractor
{
    public $nodetype = XTDOM_TYPE_TEXT;
    public $tag = 'text';
    public $attr = array();
    public $children = array();
    public $nodes = array();
    public $parent = null;
    // The "info" array - see XTDOM_INFO_... for what each element contains.
    public $_ = array();
    public $tag_start = 0;
    private $dom = null;

    function __construct($dom)
    {
        self::$dom = $dom;
        $dom->nodes[] = self;
    }

    function __destruct()
    {
        self::clear();
    }

    function __toString()
    {
        return self::outertext();
    }

    // clean up memory due to php5 circular references memory leak...
    function clear()
    {
        self::$dom = null;
        self::$nodes = null;
        self::$parent = null;
        self::$children = null;
    }

    // dump node's tree
    function dump($show_attr=true, $deep=0)
    {
        $lead = str_repeat('    ', $deep);

        echo $lead.self::$tag;
        if ($show_attr && count(self::$attr)>0)
        {
            echo '(';
            foreach (self::$attr as $k=>$v)
                echo "[$k]=>\"".self::$k.'", ';
            echo ')';
        }
        echo "\n";

        if (self::$nodes)
        {
            foreach (self::$nodes as $c)
            {
                $c->dump($show_attr, $deep+1);
            }
        }
    }


    // Debugging function to dump a single dom node with a bunch of information about it.
    function dump_node($echo=true)
    {

        $string = self::$tag;
        if (count(self::$attr)>0)
        {
            $string .= '(';
            foreach (self::$attr as $k=>$v)
            {
                $string .= "[$k]=>\"".self::$k.'", ';
            }
            $string .= ')';
        }
        if (count(self::$_)>0)
        {
            $string .= ' $_ (';
            foreach (self::$_ as $k=>$v)
            {
                if (is_array($v))
                {
                    $string .= "[$k]=>(";
                    foreach ($v as $k2=>$v2)
                    {
                        $string .= "[$k2]=>\"".$v2.'", ';
                    }
                    $string .= ")";
                } else {
                    $string .= "[$k]=>\"".$v.'", ';
                }
            }
            $string .= ")";
        }

        if (!empty(self::$text))
        {
            $string .= " text: (" . self::$text . ")";
        }

        $string .= " XTDOM_INNER_INFO: '";
        if (isset($node->_[XTDOM_INFO_INNER]))
        {
            $string .= $node->_[XTDOM_INFO_INNER] . "'";
        }
        else
        {
            $string .= ' NULL ';
        }

        $string .= " children: " . count(self::$children);
        $string .= " nodes: " . count(self::$nodes);
        $string .= " tag_start: " . self::$tag_start;
        $string .= "\n";

        if ($echo)
        {
            echo $string;
            return;
        }
        else
        {
            return $string;
        }
    }

    // returns the parent of node
    // If a node is passed in, it will reset the parent of the current node to that one.
    function parent($parent=null)
    {
        // I am SURE that this doesn't work properly.
        // It fails to unset the current node from it's current parents nodes or children list first.
        if ($parent !== null)
        {
            self::$parent = $parent;
            self::$parent->nodes[] = self;
            self::$parent->children[] = self;
        }

        return self::$parent;
    }

    // verify that node has children
    function has_child()
    {
        return !empty(self::$children);
    }

    // returns children of node
    function children($idx=-1)
    {
        if ($idx===-1)
        {
            return self::$children;
        }
        if (!empty(self::$children[$idx])) return self::$children[$idx];
        return null;
    }

    // returns the first child of node
    function first_child()
    {
        if (count(self::$children)>0)
        {
            return self::$children[0];
        }
        return null;
    }

    // returns the last child of node
    function last_child()
    {
        if (($count=count(self::$children))>0)
        {
            return self::$children[$count-1];
        }
        return null;
    }

    // returns the next sibling of node
    function next_sibling()
    {
        if (self::$parent===null)
        {
            return null;
        }

        $idx = 0;
        $count = count(self::$parent->children);
        while ($idx<$count && self!==self::$parent->children[$idx])
        {
            ++$idx;
        }
        if (++$idx>=$count)
        {
            return null;
        }
        return self::$parent->children[$idx];
    }

    // returns the previous sibling of node
    function prev_sibling()
    {
        if (self::$parent===null) return null;
        $idx = 0;
        $count = count(self::$parent->children);
        while ($idx<$count && self!==self::$parent->children[$idx])
            ++$idx;
        if (--$idx<0) return null;
        return self::$parent->children[$idx];
    }

    // function to locate a specific ancestor tag in the path to the root.
    function find_ancestor_tag($tag)
    {
        global $debugObject;
        if (is_object($debugObject)) { $debugObject->debugLogEntry(1); }

        // Start by including ourselves in the comparison.
        $returnDom = self;

        while (!is_null($returnDom))
        {
            if (is_object($debugObject)) { $debugObject->debugLog(2, "Current tag is: " . $returnDom->tag); }

            if ($returnDom->tag == $tag)
            {
                break;
            }
            $returnDom = $returnDom->parent;
        }
        return $returnDom;
    }

    // get dom node's inner html
    function innertext()
    {
        if (!empty(self::$_[XTDOM_INFO_INNER])) return self::$_[XTDOM_INFO_INNER];
        if (!empty(self::$_[XTDOM_INFO_TEXT])) return self::$dom->restore_noise(self::$_[XTDOM_INFO_TEXT]);

        $ret = '';
        foreach (self::$nodes as $n)
            $ret .= $n->outertext();
        return $ret;
    }

    // get dom node's outer text (with tag)
    function outertext()
    {
        global $debugObject;
        if (is_object($debugObject))
        {
            $text = '';
            if (self::$tag == 'text')
            {
                if (!empty(self::$text))
                {
                    $text = " with text: " . self::$text;
                }
            }
            $debugObject->debugLog(1, 'Innertext of tag: ' . self::$tag . $text);
        }

        if (self::$tag==='root') return self::innertext();

        // trigger callback
        if (self::$dom && self::$dom->callback!==null)
        {
            call_user_func_array(self::$dom->callback, array(self));
        }

        if (!empty(self::$_[XTDOM_INFO_OUTER])) return self::$_[XTDOM_INFO_OUTER];
        if (!empty(self::$_[XTDOM_INFO_TEXT])) return self::$dom->restore_noise(self::$_[XTDOM_INFO_TEXT]);

        // render begin tag
        if (self::$dom && self::$dom->nodes[self::$_[XTDOM_INFO_BEGIN]])
        {
            $ret = self::$dom->nodes[self::$_[XTDOM_INFO_BEGIN]]->makeup();
        } else {
            $ret = "";
        }

        // render inner text
        if (!empty(self::$_[XTDOM_INFO_INNER]))
        {
            // If it's a br tag...  don't return the XTDOM_INNER_INFO that we may or may not have added.
            if (self::$tag != "br")
            {
                $ret .= self::$_[XTDOM_INFO_INNER];
            }
        } else {
            if (self::$nodes)
            {
                foreach (self::$nodes as $n)
                {
                    $ret .= self::convert_text($n->outertext());
                }
            }
        }

        // render end tag
        if (!empty(self::$_[XTDOM_INFO_END]) && self::$_[XTDOM_INFO_END]!=0)
            $ret .= '</'.self::$tag.'>';
        return $ret;
    }

    // get dom node's plain text
    function text()
    {
        if (!empty(self::$_[XTDOM_INFO_INNER])) return self::$_[XTDOM_INFO_INNER];
        switch (self::nodetype)
        {
            case XTDOM_TYPE_TEXT: return self::$dom->restore_noise(self::$_[XTDOM_INFO_TEXT]);
            case XTDOM_TYPE_COMMENT: return '';
            case XTDOM_TYPE_UNKNOWN: return '';
        }
        if (strcasecmp(self::$tag, 'script')===0) return '';
        if (strcasecmp(self::$tag, 'style')===0) return '';

        $ret = '';
        // In rare cases, (always node type 1 or XTDOM_TYPE_ELEMENT - observed for some span tags, and some p tags) self::$nodes is set to NULL.
        // NOTE: This indicates that there is a problem where it's set to NULL without a clear happening.
        // WHY is this happening?
        if (!is_null(self::$nodes))
        {
            foreach (self::$nodes as $n)
            {
                $ret .= self::convert_text($n->text());
            }

            // If this node is a span... add a space at the end of it so multiple spans don't run into each other.  This is plaintext after all.
            if (self::$tag == "span")
            {
                $ret .= self::$dom->default_span_text;
            }


        }
        return $ret;
    }

    function xmltext()
    {
        $ret = self::innertext();
        $ret = str_ireplace('<![CDATA[', '', $ret);
        $ret = str_replace(']]>', '', $ret);
        return $ret;
    }

    // build node's text with tag
    function makeup()
    {
        // text, comment, unknown
        if (!empty(self::$_[XTDOM_INFO_TEXT])) return self::$dom->restore_noise(self::$_[XTDOM_INFO_TEXT]);

        $ret = '<'.self::$tag;
        $i = -1;

        foreach (self::$attr as $key=>$val)
        {
            ++$i;

            // skip removed attribute
            if ($val===null || $val===false)
                continue;

            $ret .= self::$_[XTDOM_INFO_SPACE][$i][0];
            //no value attr: nowrap, checked selected...
            if ($val===true)
                $ret .= $key;
            else {
                switch (self::$_[XTDOM_INFO_QUOTE][$i])
                {
                    case XTDOM_QUOTE_DOUBLE: $quote = '"'; break;
                    case XTDOM_QUOTE_SINGLE: $quote = '\''; break;
                    default: $quote = '';
                }
                $ret .= $key.self::$_[XTDOM_INFO_SPACE][$i][1].'='.self::$_[XTDOM_INFO_SPACE][$i][2].$quote.$val.$quote;
            }
        }
        $ret = self::$dom->restore_noise($ret);
        return $ret . self::$_[XTDOM_INFO_ENDSPACE] . '>';
    }

    // find elements by css selector
    //PaperG - added ability for find to lowercase the value of the selector.
    function find($selector, $idx=null, $lowercase=false)
    {
        $selectors = self::parse_selector($selector);
        if (($count=count($selectors))===0) return array();
        $found_keys = array();

        // find each selector
        for ($c=0; $c<$count; ++$c)
        {
            // The change on the below line was documented on the sourceforge code tracker id 2788009
            // used to be: if (($levle=count($selectors[0]))===0) return array();
            if (($levle=count($selectors[$c]))===0) return array();
            if (!!empty(self::$_[XTDOM_INFO_BEGIN])) return array();

            $head = array(self::$_[XTDOM_INFO_BEGIN]=>1);

            // handle descendant selectors, no recursive!
            for ($l=0; $l<$levle; ++$l)
            {
                $ret = array();
                foreach ($head as $k=>$v)
                {
                    $n = ($k===-1) ? self::$dom->root : self::$dom->nodes[$k];
                    //PaperG - Pass this optional parameter on to the seek function.
                    $n->seek($selectors[$c][$l], $ret, $lowercase);
                }
                $head = $ret;
            }

            foreach ($head as $k=>$v)
            {
                if (!isset($found_keys[$k]))
                    $found_keys[$k] = 1;
            }
        }

        // sort keys
        ksort($found_keys);

        $found = array();
        foreach ($found_keys as $k=>$v)
            $found[] = self::$dom->nodes[$k];

        // return nth-element or array
        if (is_null($idx)) return $found;
        else if ($idx<0) $idx = count($found) + $idx;
        return (isset($found[$idx])) ? $found[$idx] : null;
    }

    // seek for given conditions
    // PaperG - added parameter to allow for case insensitive testing of the value of a selector.
    protected function seek($selector, &$ret, $lowercase=false)
    {
        global $debugObject;
        if (is_object($debugObject)) { $debugObject->debugLogEntry(1); }

        list($tag, $key, $val, $exp, $no_key) = $selector;

        // xpath index
        if ($tag && $key && is_numeric($key))
        {
            $count = 0;
            foreach (self::$children as $c)
            {
                if ($tag==='*' || $tag===$c->tag) {
                    if (++$count==$key) {
                        $ret[$c->_[XTDOM_INFO_BEGIN]] = 1;
                        return;
                    }
                }
            }
            return;
        }

        $end = (!empty(self::$_[XTDOM_INFO_END])) ? self::$_[XTDOM_INFO_END] : 0;
        if ($end==0) {
            $parent = self::$parent;
            while (!isset($parent->_[XTDOM_INFO_END]) && $parent!==null) {
                $end -= 1;
                $parent = $parent->parent;
            }
            $end += $parent->_[XTDOM_INFO_END];
        }

        for ($i=self::$_[XTDOM_INFO_BEGIN]+1; $i<$end; ++$i) {
            $node = self::$dom->nodes[$i];

            $pass = true;

            if ($tag==='*' && !$key) {
                if (in_array($node, self::$children, true))
                    $ret[$i] = 1;
                continue;
            }

            // compare tag
            if ($tag && $tag!=$node->tag && $tag!=='*') {$pass=false;}
            // compare key
            if ($pass && $key) {
                if ($no_key) {
                    if (isset($node->attr[$key])) $pass=false;
                } else {
                    if (($key != "plaintext") && !isset($node->attr[$key])) $pass=false;
                }
            }
            // compare value
            if ($pass && $key && $val  && $val!=='*') {
                // If they have told us that this is a "plaintext" search then we want the plaintext of the node - right?
                if ($key == "plaintext") {
                    // $node->plaintext actually returns $node->text();
                    $nodeKeyValue = $node->text();
                } else {
                    // this is a normal search, we want the value of that attribute of the tag.
                    $nodeKeyValue = $node->attr[$key];
                }
                if (is_object($debugObject)) {$debugObject->debugLog(2, "testing node: " . $node->tag . " for attribute: " . $key . $exp . $val . " where nodes value is: " . $nodeKeyValue);}

                //PaperG - If lowercase is set, do a case insensitive test of the value of the selector.
                if ($lowercase) {
                    $check = self::match($exp, strtolower($val), strtolower($nodeKeyValue));
                } else {
                    $check = self::match($exp, $val, $nodeKeyValue);
                }
                if (is_object($debugObject)) {$debugObject->debugLog(2, "after match: " . ($check ? "true" : "false"));}

                // handle multiple class
                if (!$check && strcasecmp($key, 'class')===0) {
                    foreach (explode(' ',$node->attr[$key]) as $k) {
                        // Without this, there were cases where leading, trailing, or double spaces lead to our comparing blanks - bad form.
                        if (!empty($k)) {
                            if ($lowercase) {
                                $check = self::match($exp, strtolower($val), strtolower($k));
                            } else {
                                $check = self::match($exp, $val, $k);
                            }
                            if ($check) break;
                        }
                    }
                }
                if (!$check) $pass = false;
            }
            if ($pass) $ret[$i] = 1;
            unset($node);
        }
        // It's passed by reference so this is actually what this function returns.
        if (is_object($debugObject)) {$debugObject->debugLog(1, "EXIT - ret: ", $ret);}
    }

    protected function match($exp, $pattern, $value) {
        global $debugObject;
        if (is_object($debugObject)) {$debugObject->debugLogEntry(1);}

        switch ($exp) {
            case '=':
                return ($value===$pattern);
            case '!=':
                return ($value!==$pattern);
            case '^=':
                return preg_match("/^".preg_quote($pattern,'/')."/", $value);
            case '$=':
                return preg_match("/".preg_quote($pattern,'/')."$/", $value);
            case '*=':
                if ($pattern[0]=='/') {
                    return preg_match($pattern, $value);
                }
                return preg_match("/".$pattern."/i", $value);
        }
        return false;
    }

    protected function parse_selector($selector_string) {
        global $debugObject;
        if (is_object($debugObject)) {$debugObject->debugLogEntry(1);}

        // pattern of CSS selectors, modified from mootools
        // Paperg: Add the colon to the attrbute, so that it properly finds <tag attr:ibute="something" > like google does.
        // Note: if you try to look at this attribute, yo MUST use getAttribute since $dom->x:y will fail the php syntax check.
// Notice the \[ starting the attbute?  and the @? following?  This implies that an attribute can begin with an @ sign that is not captured.
// This implies that an html attribute specifier may start with an @ sign that is NOT captured by the expression.
// farther study is required to determine of this should be documented or removed.
//        $pattern = "/([\w-:\*]*)(?:\#([\w-]+)|\.([\w-]+))?(?:\[@?(!?[\w-]+)(?:([!*^$]?=)[\"']?(.*?)[\"']?)?\])?([\/, ]+)/is";
        $pattern = "/([\w-:\*]*)(?:\#([\w-]+)|\.([\w-]+))?(?:\[@?(!?[\w-:]+)(?:([!*^$]?=)[\"']?(.*?)[\"']?)?\])?([\/, ]+)/is";
        preg_match_all($pattern, trim($selector_string).' ', $matches, PREG_SET_ORDER);
        if (is_object($debugObject)) {$debugObject->debugLog(2, "Matches Array: ", $matches);}

        $selectors = array();
        $result = array();
        //print_r($matches);

        foreach ($matches as $m) {
            $m[0] = trim($m[0]);
            if ($m[0]==='' || $m[0]==='/' || $m[0]==='//') continue;
            // for browser generated xpath
            if ($m[1]==='tbody') continue;

            list($tag, $key, $val, $exp, $no_key) = array($m[1], null, null, '=', false);
            if (!empty($m[2])) {$key='id'; $val=$m[2];}
            if (!empty($m[3])) {$key='class'; $val=$m[3];}
            if (!empty($m[4])) {$key=$m[4];}
            if (!empty($m[5])) {$exp=$m[5];}
            if (!empty($m[6])) {$val=$m[6];}

            // convert to lowercase
            if (self::$dom->lowercase) {$tag=strtolower($tag); $key=strtolower($key);}
            //elements that do NOT have the specified attribute
            if (isset($key[0]) && $key[0]==='!') {$key=substr($key, 1); $no_key=true;}

            $result[] = array($tag, $key, $val, $exp, $no_key);
            if (trim($m[7])===',') {
                $selectors[] = $result;
                $result = array();
            }
        }
        if (count($result)>0)
            $selectors[] = $result;
        return $selectors;
    }

    function __get($name) {
        if (!empty(self::$attr[$name]))
        {
            return self::convert_text(self::$attr[$name]);
        }
        switch ($name) {
            case 'outertext': return self::outertext();
            case 'innertext': return self::innertext();
            case 'plaintext': return self::$text();
            case 'xmltext': return self::xmltext();
            default: return array_key_exists($name, self::$attr);
        }
    }

    function __set($name, $value) {
        switch ($name) {
            case 'outertext': return self::$_[XTDOM_INFO_OUTER] = $value;
            case 'innertext':
                if (!empty(self::$_[XTDOM_INFO_TEXT])) return self::$_[XTDOM_INFO_TEXT] = $value;
                return self::$_[XTDOM_INFO_INNER] = $value;
        }
        if (!!empty(self::$attr[$name])) {
            self::$_[XTDOM_INFO_SPACE][] = array(' ', '', '');
            self::$_[XTDOM_INFO_QUOTE][] = XTDOM_QUOTE_DOUBLE;
        }
        self::$attr[$name] = $value;
    }

    function __isset($name) {
        switch ($name) {
            case 'outertext': return true;
            case 'innertext': return true;
            case 'plaintext': return true;
        }
        //no value attr: nowrap, checked selected...
        return (array_key_exists($name, self::$attr)) ? true : !empty(self::$attr[$name]);
    }

    function __unset($name) {
        if (!empty(self::$attr[$name]))
            unset(self::$attr[$name]);
    }

    // PaperG - Function to convert the text from one character set to another if the two sets are not the same.
    function convert_text($text)
    {
        global $debugObject;
        if (is_object($debugObject)) {$debugObject->debugLogEntry(1);}

        $converted_text = $text;

        $sourceCharset = "";
        $targetCharset = "";

        if (self::$dom)
        {
            $sourceCharset = strtoupper(self::$dom->_charset);
            $targetCharset = strtoupper(self::$dom->_target_charset);
        }
        if (is_object($debugObject)) {$debugObject->debugLog(3, "source charset: " . $sourceCharset . " target charaset: " . $targetCharset);}

        if (!empty($sourceCharset) && !empty($targetCharset) && (strcasecmp($sourceCharset, $targetCharset) != 0))
        {
            // Check if the reported encoding could have been incorrect and the text is actually already UTF-8
            if ((strcasecmp($targetCharset, 'UTF-8') == 0) && (self::is_utf8($text)))
            {
                $converted_text = $text;
            }
            else
            {
                $converted_text = iconv($sourceCharset, $targetCharset, $text);
            }
        }

        // Lets make sure that we don't have that silly BOM issue with any of the utf-8 text we output.
        if ($targetCharset == 'UTF-8')
        {
            if (substr($converted_text, 0, 3) == "\xef\xbb\xbf")
            {
                $converted_text = substr($converted_text, 3);
            }
            if (substr($converted_text, -3) == "\xef\xbb\xbf")
            {
                $converted_text = substr($converted_text, 0, -3);
            }
        }

        return $converted_text;
    }

    /**
    * Returns true if $string is valid UTF-8 and false otherwise.
    *
    * @param mixed $str String to be tested
    * @return boolean
    */
    static function is_utf8($str)
    {
        $c=0; $b=0;
        $bits=0;
        $len=strlen($str);
        for($i=0; $i<$len; $i++)
        {
            $c=ord($str[$i]);
            if($c > 128)
            {
                if(($c >= 254)) return false;
                elseif($c >= 252) $bits=6;
                elseif($c >= 248) $bits=5;
                elseif($c >= 240) $bits=4;
                elseif($c >= 224) $bits=3;
                elseif($c >= 192) $bits=2;
                else return false;
                if(($i+$bits) > $len) return false;
                while($bits > 1)
                {
                    $i++;
                    $b=ord($str[$i]);
                    if($b < 128 || $b > 191) return false;
                    $bits--;
                }
            }
        }
        return true;
    }

    /**
     * Function to try a few tricks to determine the displayed size of an img on the page.
     * NOTE: This will ONLY work on an IMG tag. Returns FALSE on all other tag types.
     *
     * @author Simon Roberts <cipher@labs.coop>
     * @version September 25 2014
     * @return array an array containing the 'height' and 'width' of the image on the page or -1 if we can't figure it out.
     */
    function get_display_size()
    {
        global $debugObject;

        $width = -1;
        $height = -1;

        if (self::$tag !== 'img')
        {
            return false;
        }

        // See if there is aheight or width attribute in the tag itself.
        if (!empty(self::$attr['width']))
        {
            $width = self::$attr['width'];
        }

        if (!empty(self::$attr['height']))
        {
            $height = self::$attr['height'];
        }

        // Now look for an inline style.
        if (!empty(self::$attr['style']))
        {
            // Thanks to user gnarf from stackoverflow for this regular expression.
            $attributes = array();
            preg_match_all("/([\w-]+)\s*:\s*([^;]+)\s*;?/", self::$attr['style'], $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
              $attributes[$match[1]] = $match[2];
            }

            // If there is a width in the style attributes:
            if (isset($attributes['width']) && $width == -1)
            {
                // check that the last two characters are px (pixels)
                if (strtolower(substr($attributes['width'], -2)) == 'px')
                {
                    $proposed_width = substr($attributes['width'], 0, -2);
                    // Now make sure that it's an integer and not something stupid.
                    if (filter_var($proposed_width, FILTER_VALIDATE_INT))
                    {
                        $width = $proposed_width;
                    }
                }
            }

            // If there is a width in the style attributes:
            if (isset($attributes['height']) && $height == -1)
            {
                // check that the last two characters are px (pixels)
                if (strtolower(substr($attributes['height'], -2)) == 'px')
                {
                    $proposed_height = substr($attributes['height'], 0, -2);
                    // Now make sure that it's an integer and not something stupid.
                    if (filter_var($proposed_height, FILTER_VALIDATE_INT))
                    {
                        $height = $proposed_height;
                    }
                }
            }

        }

        // Future enhancement:
        // Look in the tag to see if there is a class or id specified that has a height or width attribute to it.

        // Far future enhancement
        // Look at all the parent tags of this image to see if they specify a class or id that has an img selector that specifies a height or width
        // Note that in this case, the class or id will have the img subselector for it to apply to the image.

        // ridiculously far future development
        // If the class or id is specified in a SEPARATE css file thats not on the page, go get it and do what we were just doing for the ones on the page.

        $result = array('height' => $height,
                        'width' => $width);
        return $result;
    }

    // camel naming conventions
    function getAllAttributes() {return self::$attr;}
    function getAttribute($name) {return self::$__get($name);}
    function setAttribute($name, $value) {self::$__set($name, $value);}
    function hasAttribute($name) {return self::$__isset($name);}
    function removeAttribute($name) {self::$__set($name, null);}
    function getElementById($id) {return self::find("#$id", 0);}
    function getElementsById($id, $idx=null) {return self::find("#$id", $idx);}
    function getElementByTagName($name) {return self::find($name, 0);}
    function getElementsByTagName($name, $idx=null) {return self::find($name, $idx);}
    function parentNode() {return self::$parent();}
    function childNodes($idx=-1) {return self::$children($idx);}
    function firstChild() {return self::first_child();}
    function lastChild() {return self::last_child();}
    function nextSibling() {return self::next_sibling();}
    function previousSibling() {return self::prev_sibling();}
    function hasChildNodes() {return self::has_child();}
    function nodeName() {return self::$tag;}
    function appendChild($node) {$node->parent(self); return $node;}

}

/**
 * dom parser
 * in the find routine: allow us to specify that we want case insensitive testing of the value of the selector.
 * change $size from protected to public so we can easily access it
 * added ForceTagsClosed in the constructor which tells us whether we trust the html or not.  Default is to NOT trust it.
 *
 */
final class xtdom
{
    public $root = null;
    public $nodes = array();
    public $callback = null;
    public $lowercase = false;
    // Used to keep track of how large the text was when we started.
    public $original_size;
    public $size;
    protected $pos;
    protected $doc;
    protected $char;
    protected $cursor;
    protected $parent;
    protected $noise = array();
    protected $token_blank = " \t\r\n";
    protected $token_equal = ' =/>';
    protected $token_slash = " />\r\n\t";
    protected $token_attr = ' >';
    // Note that this is referenced by a child node, and so it needs to be public for that node to see this information.
    public $_charset = '';
    public $_target_charset = '';
    protected $default_br_text = "";
    public $default_span_text = "";

    // use isset instead of in_array, performance boost about 30%...
    protected $self_closing_tags = array('img'=>1, 'br'=>1, 'input'=>1, 'meta'=>1, 'link'=>1, 'hr'=>1, 'base'=>1, 'embed'=>1, 'spacer'=>1);
    protected $block_tags = array('root'=>1, 'body'=>1, 'form'=>1, 'div'=>1, 'span'=>1, 'table'=>1);
    // Known sourceforge issue #2977341
    // B tags that are not closed cause us to return everything to the end of the document.
    protected $optional_closing_tags = array(
        'tr'=>array('tr'=>1, 'td'=>1, 'th'=>1),
        'th'=>array('th'=>1),
        'td'=>array('td'=>1),
        'li'=>array('li'=>1),
        'dt'=>array('dt'=>1, 'dd'=>1),
        'dd'=>array('dd'=>1, 'dt'=>1),
        'dl'=>array('dd'=>1, 'dt'=>1),
        'p'=>array('p'=>1),
        'nobr'=>array('nobr'=>1),
        'b'=>array('b'=>1),
		'option'=>array('option'=>1),
    );

    function __construct($str=null, $lowercase=true, $forceTagsClosed=true, $target_charset=XTDOM_DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=XTDOM_DEFAULT_BR_TEXT, $defaultSpanText=XTDOM_DEFAULT_SPAN_TEXT)
    {
        if ($str)
        {
            if (preg_match("/^http:\/\//i",$str) || is_file($str))
            {
                self::load_file($str);
            }
            else
            {
                self::load($str, $lowercase, $stripRN, $defaultBRText, $defaultSpanText);
            }
        }
        // Forcing tags to be closed implies that we don't trust the html, but it can lead to parsing errors if we SHOULD trust the html.
        if (!$forceTagsClosed) {
            self::$optional_closing_array=array();
        }
        self::$_target_charset = $target_charset;
    }

    function __destruct()
    {
        self::clear();
    }

    // load html from string
    function load($str, $lowercase=true, $stripRN=true, $defaultBRText=XTDOM_DEFAULT_BR_TEXT, $defaultSpanText=XTDOM_DEFAULT_SPAN_TEXT)
    {
        global $debugObject;

        // prepare
        self::prepare($str, $lowercase, $stripRN, $defaultBRText, $defaultSpanText);
        // strip out comments
        self::remove_noise("'<!--(.*?)-->'is");
        // strip out cdata
        self::remove_noise("'<!\[CDATA\[(.*?)\]\]>'is", true);
        // Per sourceforge http://sourceforge.net/tracker/?func=detail&aid=2949097&group_id=218559&atid=1044037
        // Script tags removal now preceeds style tag removal.
        // strip out <script> tags
        self::remove_noise("'<\s*script[^>]*[^/]>(.*?)<\s*/\s*script\s*>'is");
        self::remove_noise("'<\s*script\s*>(.*?)<\s*/\s*script\s*>'is");
        // strip out <style> tags
        self::remove_noise("'<\s*style[^>]*[^/]>(.*?)<\s*/\s*style\s*>'is");
        self::remove_noise("'<\s*style\s*>(.*?)<\s*/\s*style\s*>'is");
        // strip out preformatted tags
        self::remove_noise("'<\s*(?:code)[^>]*>(.*?)<\s*/\s*(?:code)\s*>'is");
        // strip out server side scripts
        self::remove_noise("'(<\?)(.*?)(\?>)'s", true);
        // strip smarty scripts
        self::remove_noise("'(\{\w)(.*?)(\})'s", true);

        // parsing
        while (self::parse());
        // end
        self::$root->_[XTDOM_INFO_END] = self::$cursor;
        self::parse_charset();

        // make load function chainable
        return self;

    }

    // load html from file
    function load_file()
    {
        $args = func_get_args();
        self::load(call_user_func_array('file_get_contents', $args), true);
        // Throw an error if we can't properly load the dom.
        if (($error=error_get_last())!==null) {
            self::clear();
            return false;
        }
    }

    // set callback function
    function set_callback($function_name)
    {
        self::$callback = $function_name;
    }

    // remove callback function
    function remove_callback()
    {
        self::$callback = null;
    }

    // save dom as string
    function save($filepath='')
    {
        $ret = self::$root->innertext();
        if ($filepath!=='') file_put_contents($filepath, $ret, LOCK_EX);
        return $ret;
    }

    // find dom node by css selector
    // Paperg - allow us to specify that we want case insensitive testing of the value of the selector.
    function find($selector, $idx=null, $lowercase=false)
    {
        return self::$root->find($selector, $idx, $lowercase);
    }

    // clean up memory due to php5 circular references memory leak...
    function clear()
    {
        foreach (self::$nodes as $n) {$n->clear(); $n = null;}
        // This add next line is documented in the sourceforge repository. 2977248 as a fix for ongoing memory leaks that occur even with the use of clear.
        if (!empty(self::$children)) foreach (self::$children as $n) {$n->clear(); $n = null;}
        if (!empty(self::$parent)) {self::$parent->clear(); unset(self::$parent);}
        if (!empty(self::$root)) {self::$root->clear(); unset(self::$root);}
        unset(self::$doc);
        unset(self::$noise);
    }

    function dump($show_attr=true)
    {
        self::$root->dump($show_attr);
    }

    // prepare HTML data and init everything
    protected function prepare($str, $lowercase=true, $stripRN=true, $defaultBRText=XTDOM_DEFAULT_BR_TEXT, $defaultSpanText=XTDOM_DEFAULT_SPAN_TEXT)
    {
        self::clear();

        // set the length of content before we do anything to it.
        self::$size = strlen($str);
        // Save the original size of the html that we got in.  It might be useful to someone.
        self::$original_size = self::$size;

        //before we save the string as the doc...  strip out the \r \n's if we are told to.
        if ($stripRN) {
            $str = str_replace("\r", " ", $str);
            $str = str_replace("\n", " ", $str);

            // set the length of content since we have changed it.
            self::$size = strlen($str);
        }

        self::$doc = $str;
        self::$pos = 0;
        self::$cursor = 1;
        self::$noise = array();
        self::$nodes = array();
        self::$lowercase = $lowercase;
        self::$default_br_text = $defaultBRText;
        self::$default_span_text = $defaultSpanText;
        self::$root = new xtractor(self);
        self::$root->tag = 'root';
        self::$root->_[XTDOM_INFO_BEGIN] = -1;
        self::$root->nodetype = XTDOM_TYPE_ROOT;
        self::$parent = self::$root;
        if (self::$size>0) self::$char = self::$doc[0];
    }

    // parse html content
    protected function parse()
    {
        if (($s = self::copy_until_char('<'))==='')
        {
            return self::read_tag();
        }

        // text
        $node = new xtractor(self);
        ++self::$cursor;
        $node->_[XTDOM_INFO_TEXT] = $s;
        self::link_nodes($node, false);
        return true;
    }

    // PAPERG - dkchou - added this to try to identify the character set of the page we have just parsed so we know better how to spit it out later.
    // NOTE:  IF you provide a routine called get_last_retrieve_url_contents_content_type which returns the CURLINFO_CONTENT_TYPE from the last curl_exec
    // (or the content_type header from the last transfer), we will parse THAT, and if a charset is specified, we will use it over any other mechanism.
    protected function parse_charset()
    {
        global $debugObject;

        $charset = null;

        if (function_exists('get_last_retrieve_url_contents_content_type'))
        {
            $contentTypeHeader = get_last_retrieve_url_contents_content_type();
            $success = preg_match('/charset=(.+)/', $contentTypeHeader, $matches);
            if ($success)
            {
                $charset = $matches[1];
                if (is_object($debugObject)) {$debugObject->debugLog(2, 'header content-type found charset of: ' . $charset);}
            }

        }

        if (empty($charset))
        {
            $el = self::$root->find('meta[http-equiv=Content-Type]',0);
            if (!empty($el))
            {
                $fullvalue = $el->content;
                if (is_object($debugObject)) {$debugObject->debugLog(2, 'meta content-type tag found' . $fullvalue);}

                if (!empty($fullvalue))
                {
                    $success = preg_match('/charset=(.+)/', $fullvalue, $matches);
                    if ($success)
                    {
                        $charset = $matches[1];
                    }
                    else
                    {
                        // If there is a meta tag, and they don't specify the character set, research says that it's typically ISO-8859-1
                        if (is_object($debugObject)) {$debugObject->debugLog(2, 'meta content-type tag couldn\'t be parsed. using iso-8859 default.');}
                        $charset = 'ISO-8859-1';
                    }
                }
            }
        }

        // If we couldn't find a charset above, then lets try to detect one based on the text we got...
        if (empty($charset))
        {
            // Have php try to detect the encoding from the text given to us.
            $charset = mb_detect_encoding(self::$root->plaintext . "ascii", $encoding_list = array( "UTF-8", "CP1252" ) );
            if (is_object($debugObject)) {$debugObject->debugLog(2, 'mb_detect found: ' . $charset);}

            // and if this doesn't work...  then we need to just wrongheadedly assume it's UTF-8 so that we can move on - cause this will usually give us most of what we need...
            if ($charset === false)
            {
                if (is_object($debugObject)) {$debugObject->debugLog(2, 'since mb_detect failed - using default of utf-8');}
                $charset = 'UTF-8';
            }
        }

        // Since CP1252 is a superset, if we get one of it's subsets, we want it instead.
        if ((strtolower($charset) == strtolower('ISO-8859-1')) || (strtolower($charset) == strtolower('Latin1')) || (strtolower($charset) == strtolower('Latin-1')))
        {
            if (is_object($debugObject)) {$debugObject->debugLog(2, 'replacing ' . $charset . ' with CP1252 as its a superset');}
            $charset = 'CP1252';
        }

        if (is_object($debugObject)) {$debugObject->debugLog(1, 'EXIT - ' . $charset);}

        return self::$_charset = $charset;
    }

    // read tag info
    protected function read_tag()
    {
        if (self::$char!=='<')
        {
            self::$root->_[XTDOM_INFO_END] = self::$cursor;
            return false;
        }
        $begin_tag_pos = self::$pos;
        self::$char = (++self::$pos<self::$size) ? self::$doc[self::$pos] : null; // next

        // end tag
        if (self::$char==='/')
        {
            self::$char = (++self::$pos<self::$size) ? self::$doc[self::$pos] : null; // next
            // This represents the change in the xtdom trunk from revision 180 to 181.
            // self::skip(self::$token_blank_t);
            self::skip(self::$token_blank);
            $tag = self::copy_until_char('>');

            // skip attributes in end tag
            if (($pos = strpos($tag, ' '))!==false)
                $tag = substr($tag, 0, $pos);

            $parent_lower = strtolower(self::$parent->tag);
            $tag_lower = strtolower($tag);

            if ($parent_lower!==$tag_lower)
            {
                if (!empty(self::$optional_closing_tags[$parent_lower]) && !empty(self::$block_tags[$tag_lower]))
                {
                    self::$parent->_[XTDOM_INFO_END] = 0;
                    $org_parent = self::$parent;

                    while ((self::$parent->parent) && strtolower(self::$parent->tag)!==$tag_lower)
                        self::$parent = self::$parent->parent;

                    if (strtolower(self::$parent->tag)!==$tag_lower) {
                        self::$parent = $org_parent; // restore origonal parent
                        if (self::$parent->parent) self::$parent = self::$parent->parent;
                        self::$parent->_[XTDOM_INFO_END] = self::$cursor;
                        return self::as_text_node($tag);
                    }
                }
                else if ((self::$parent->parent) && !empty(self::$block_tags[$tag_lower]))
                {
                    self::$parent->_[XTDOM_INFO_END] = 0;
                    $org_parent = self::$parent;

                    while ((self::$parent->parent) && strtolower(self::$parent->tag)!==$tag_lower)
                        self::$parent = self::$parent->parent;

                    if (strtolower(self::$parent->tag)!==$tag_lower)
                    {
                        self::$parent = $org_parent; // restore origonal parent
                        self::$parent->_[XTDOM_INFO_END] = self::$cursor;
                        return self::as_text_node($tag);
                    }
                }
                else if ((self::$parent->parent) && strtolower(self::$parent->parent->tag)===$tag_lower)
                {
                    self::$parent->_[XTDOM_INFO_END] = 0;
                    self::$parent = self::$parent->parent;
                }
                else
                    return self::as_text_node($tag);
            }

            self::$parent->_[XTDOM_INFO_END] = self::$cursor;
            if (self::$parent->parent) self::$parent = self::$parent->parent;

            self::$char = (++self::$pos<self::$size) ? self::$doc[self::$pos] : null; // next
            return true;
        }

        $node = new xtractor(self);
        $node->_[XTDOM_INFO_BEGIN] = self::$cursor;
        ++self::$cursor;
        $tag = self::copy_until(self::token_slash);
        $node->tag_start = $begin_tag_pos;

        // doctype, cdata & comments...
        if (isset($tag[0]) && $tag[0]==='!') {
            $node->_[XTDOM_INFO_TEXT] = '<' . $tag . self::copy_until_char('>');

            if (isset($tag[2]) && $tag[1]==='-' && $tag[2]==='-') {
                $node->nodetype = XTDOM_TYPE_COMMENT;
                $node->tag = 'comment';
            } else {
                $node->nodetype = XTDOM_TYPE_UNKNOWN;
                $node->tag = 'unknown';
            }
            if (self::$char==='>') $node->_[XTDOM_INFO_TEXT].='>';
            self::link_nodes($node, true);
            self::$char = (++self::$pos<self::$size) ? self::$doc[self::$pos] : null; // next
            return true;
        }

        // text
        if ($pos=strpos($tag, '<')!==false) {
            $tag = '<' . substr($tag, 0, -1);
            $node->_[XTDOM_INFO_TEXT] = $tag;
            self::link_nodes($node, false);
            self::$char = self::$doc[--self::$pos]; // prev
            return true;
        }

        if (!preg_match("/^[\w-:]+$/", $tag)) {
            $node->_[XTDOM_INFO_TEXT] = '<' . $tag . self::copy_until('<>');
            if (self::$char==='<') {
                self::link_nodes($node, false);
                return true;
            }

            if (self::$char==='>') $node->_[XTDOM_INFO_TEXT].='>';
            self::link_nodes($node, false);
            self::$char = (++self::$pos<self::$size) ? self::$doc[self::$pos] : null; // next
            return true;
        }

        // begin tag
        $node->nodetype = XTDOM_TYPE_ELEMENT;
        $tag_lower = strtolower($tag);
        $node->tag = (self::$lowercase) ? $tag_lower : $tag;

        // handle optional closing tags
        if (!empty(self::$optional_closing_tags[$tag_lower]) )
        {
            while (!empty(self::$optional_closing_tags[$tag_lower][strtolower(self::$parent->tag)]))
            {
                self::$parent->_[XTDOM_INFO_END] = 0;
                self::$parent = self::$parent->parent;
            }
            $node->parent = self::$parent;
        }

        $guard = 0; // prevent infinity loop
        $space = array(self::copy_skip(self::$token_blank), '', '');

        // attributes
        do
        {
            if (self::$char!==null && $space[0]==='')
            {
                break;
            }
            $name = self::copy_until(self::token_equal);
            if ($guard===self::$pos)
            {
                self::$char = (++self::$pos<self::$size) ? self::$doc[self::$pos] : null; // next
                continue;
            }
            $guard = self::$pos;

            // handle endless '<'
            if (self::$pos>=self::$size-1 && self::$char!=='>') {
                $node->nodetype = XTDOM_TYPE_TEXT;
                $node->_[XTDOM_INFO_END] = 0;
                $node->_[XTDOM_INFO_TEXT] = '<'.$tag . $space[0] . $name;
                $node->tag = 'text';
                self::link_nodes($node, false);
                return true;
            }

            // handle mismatch '<'
            if (self::$doc[self::$pos-1]=='<') {
                $node->nodetype = XTDOM_TYPE_TEXT;
                $node->tag = 'text';
                $node->attr = array();
                $node->_[XTDOM_INFO_END] = 0;
                $node->_[XTDOM_INFO_TEXT] = substr(self::$doc, $begin_tag_pos, self::$pos-$begin_tag_pos-1);
                self::$pos -= 2;
                self::$char = (++self::$pos<self::$size) ? self::$doc[self::$pos] : null; // next
                self::link_nodes($node, false);
                return true;
            }

            if ($name!=='/' && $name!=='') {
                $space[1] = self::copy_skip(self::$token_blank);
                $name = self::restore_noise($name);
                if (self::$lowercase) $name = strtolower($name);
                if (self::$char==='=') {
                    self::$char = (++self::$pos<self::$size) ? self::$doc[self::$pos] : null; // next
                    self::parse_attr($node, $name, $space);
                }
                else {
                    //no value attr: nowrap, checked selected...
                    $node->_[XTDOM_INFO_QUOTE][] = XTDOM_QUOTE_NO;
                    $node->attr[$name] = true;
                    if (self::$char!='>') self::$char = self::$doc[--self::$pos]; // prev
                }
                $node->_[XTDOM_INFO_SPACE][] = $space;
                $space = array(self::copy_skip(self::$token_blank), '', '');
            }
            else
                break;
        } while (self::$char!=='>' && self::$char!=='/');

        self::link_nodes($node, true);
        $node->_[XTDOM_INFO_ENDSPACE] = $space[0];

        // check self closing
        if (self::copy_until_char_escape('>')==='/')
        {
            $node->_[XTDOM_INFO_ENDSPACE] .= '/';
            $node->_[XTDOM_INFO_END] = 0;
        }
        else
        {
            // reset parent
            if (!!empty(self::$self_closing_tags[strtolower($node->tag)])) self::$parent = $node;
        }
        self::$char = (++self::$pos<self::$size) ? self::$doc[self::$pos] : null; // next

        // If it's a BR tag, we need to set it's text to the default text.
        // This way when we see it in plaintext, we can generate formatting that the user wants.
        // since a br tag never has sub nodes, this works well.
        if ($node->tag == "br")
        {
            $node->_[XTDOM_INFO_INNER] = self::$default_br_text;
        }

        return true;
    }

    // parse attributes
    protected function parse_attr($node, $name, &$space)
    {
        // Per sourceforge: http://sourceforge.net/tracker/?func=detail&aid=3061408&group_id=218559&atid=1044037
        // If the attribute is already defined inside a tag, only pay atetntion to the first one as opposed to the last one.
        if (isset($node->attr[$name]))
        {
            return;
        }

        $space[2] = self::copy_skip(self::$token_blank);
        switch (self::$char) {
            case '"':
                $node->_[XTDOM_INFO_QUOTE][] = XTDOM_QUOTE_DOUBLE;
                self::$char = (++self::$pos<self::$size) ? self::$doc[self::$pos] : null; // next
                $node->attr[$name] = self::restore_noise(self::copy_until_char_escape('"'));
                self::$char = (++self::$pos<self::$size) ? self::$doc[self::$pos] : null; // next
                break;
            case '\'':
                $node->_[XTDOM_INFO_QUOTE][] = XTDOM_QUOTE_SINGLE;
                self::$char = (++self::$pos<self::$size) ? self::$doc[self::$pos] : null; // next
                $node->attr[$name] = self::restore_noise(self::copy_until_char_escape('\''));
                self::$char = (++self::$pos<self::$size) ? self::$doc[self::$pos] : null; // next
                break;
            default:
                $node->_[XTDOM_INFO_QUOTE][] = XTDOM_QUOTE_NO;
                $node->attr[$name] = self::restore_noise(self::copy_until(self::token_attr));
        }
        // PaperG: Attributes should not have \r or \n in them, that counts as html whitespace.
        $node->attr[$name] = str_replace("\r", "", $node->attr[$name]);
        $node->attr[$name] = str_replace("\n", "", $node->attr[$name]);
        // PaperG: If this is a "class" selector, lets get rid of the preceeding and trailing space since some people leave it in the multi class case.
        if ($name == "class") {
            $node->attr[$name] = trim($node->attr[$name]);
        }
    }

    // link node's parent
    protected function link_nodes(&$node, $is_child)
    {
        $node->parent = self::$parent;
        self::$parent->nodes[] = $node;
        if ($is_child)
        {
            self::$parent->children[] = $node;
        }
    }

    // as a text node
    protected function as_text_node($tag)
    {
        $node = new xtractor(self);
        ++self::$cursor;
        $node->_[XTDOM_INFO_TEXT] = '</' . $tag . '>';
        self::link_nodes($node, false);
        self::$char = (++self::$pos<self::$size) ? self::$doc[self::$pos] : null; // next
        return true;
    }

    protected function skip($chars)
    {
        self::$pos += strspn(self::$doc, $chars, self::$pos);
        self::$char = (self::$pos<self::$size) ? self::$doc[self::$pos] : null; // next
    }

    protected function copy_skip($chars)
    {
        $pos = self::$pos;
        $len = strspn(self::$doc, $chars, $pos);
        self::$pos += $len;
        self::$char = (self::$pos<self::$size) ? self::$doc[self::$pos] : null; // next
        if ($len===0) return '';
        return substr(self::$doc, $pos, $len);
    }

    protected function copy_until($chars)
    {
        $pos = self::$pos;
        $len = strcspn(self::$doc, $chars, $pos);
        self::$pos += $len;
        self::$char = (self::$pos<self::$size) ? self::$doc[self::$pos] : null; // next
        return substr(self::$doc, $pos, $len);
    }

    protected function copy_until_char($char)
    {
        if (self::$char===null) return '';

        if (($pos = strpos(self::$doc, $char, self::$pos))===false) {
            $ret = substr(self::$doc, self::$pos, self::$size-self::$pos);
            self::$char = null;
            self::$pos = self::$size;
            return $ret;
        }

        if ($pos===self::$pos) return '';
        $pos_old = self::$pos;
        self::$char = self::$doc[$pos];
        self::$pos = $pos;
        return substr(self::$doc, $pos_old, $pos-$pos_old);
    }

    protected function copy_until_char_escape($char)
    {
        if (self::$char===null) return '';

        $start = self::$pos;
        while (1)
        {
            if (($pos = strpos(self::$doc, $char, $start))===false)
            {
                $ret = substr(self::$doc, self::$pos, self::$size-self::$pos);
                self::$char = null;
                self::$pos = self::$size;
                return $ret;
            }

            if ($pos===self::$pos) return '';

            if (self::$doc[$pos-1]==='\\') {
                $start = $pos+1;
                continue;
            }

            $pos_old = self::$pos;
            self::$char = self::$doc[$pos];
            self::$pos = $pos;
            return substr(self::$doc, $pos_old, $pos-$pos_old);
        }
    }

    // remove noise from html content
    // save the noise in the self::$noise array.
    protected function remove_noise($pattern, $remove_tag=false)
    {
        global $debugObject;
        if (is_object($debugObject)) { $debugObject->debugLogEntry(1); }

        $count = preg_match_all($pattern, self::$doc, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE);

        for ($i=$count-1; $i>-1; --$i)
        {
            $key = '___noise___'.sprintf('% 5d', count(self::$noise)+1000);
            if (is_object($debugObject)) { $debugObject->debugLog(2, 'key is: ' . $key); }
            $idx = ($remove_tag) ? 0 : 1;
            self::$noise[$key] = $matches[$i][$idx][0];
            self::$doc = substr_replace(self::$doc, $key, $matches[$i][$idx][1], strlen($matches[$i][$idx][0]));
        }

        // reset the length of content
        self::$size = strlen(self::$doc);
        if (self::$size>0)
        {
            self::$char = self::$doc[0];
        }
    }

    // restore noise to html content
    function restore_noise($text)
    {
        global $debugObject;
        if (is_object($debugObject)) { $debugObject->debugLogEntry(1); }

        while (($pos=strpos($text, '___noise___'))!==false)
        {
            // Sometimes there is a broken piece of markup, and we don't GET the pos+11 etc... token which indicates a problem outside of us...
            if (strlen($text) > $pos+15)
            {
                $key = '___noise___'.$text[$pos+11].$text[$pos+12].$text[$pos+13].$text[$pos+14].$text[$pos+15];
                if (is_object($debugObject)) { $debugObject->debugLog(2, 'located key of: ' . $key); }

                if (!empty(self::$noise[$key]))
                {
                    $text = substr($text, 0, $pos).self::$noise[$key].substr($text, $pos+16);
                }
                else
                {
                    // do this to prevent an infinite loop.
                    $text = substr($text, 0, $pos).'UNDEFINED NOISE FOR KEY: '.$key . substr($text, $pos+16);
                }
            }
            else
            {
                // There is no valid key being given back to us... We must get rid of the ___noise___ or we will have a problem.
                $text = substr($text, 0, $pos).'NO NUMERIC NOISE KEY' . substr($text, $pos+11);
            }
        }
        return $text;
    }

    // Sometimes we NEED one of the noise elements.
    function search_noise($text)
    {
        global $debugObject;
        if (is_object($debugObject)) { $debugObject->debugLogEntry(1); }

        foreach(self::$noise as $noiseElement)
        {
            if (strpos($noiseElement, $text)!==false)
            {
                return $noiseElement;
            }
        }
    }
    function __toString()
    {
        return self::$root->innertext();
    }

    function __get($name)
    {
        switch ($name)
        {
            case 'outertext':
                return self::$root->innertext();
            case 'innertext':
                return self::$root->innertext();
            case 'plaintext':
                return self::$root->text();
            case 'charset':
                return self::$_charset;
            case 'target_charset':
                return self::$_target_charset;
        }
    }

    // camel naming conventions
    function childNodes($idx=-1) {return self::$root->childNodes($idx);}
    function firstChild() {return self::$root->first_child();}
    function lastChild() {return self::$root->last_child();}
    function createElement($name, $value=null) {return @str_get_html("<$name>$value</$name>")->first_child();}
    function createTextNode($value) {return @end(str_get_html($value)->nodes);}
    function getElementById($id) {return self::find("#$id", 0);}
    function getElementsById($id, $idx=null) {return self::find("#$id", $idx);}
    function getElementByTagName($name) {return self::find($name, 0);}
    function getElementsByTagName($name, $idx=-1) {return self::find($name, $idx);}
    function loadFile() {$args = func_get_args();self::load_file($args);}
}

?>