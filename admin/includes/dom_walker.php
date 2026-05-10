<?php
/**
 * Deterministic DOM walker.
 * Loads HTML, traverses every element in document order, and assigns
 * data-ie-id="N" attributes (where N is a sequential counter).
 *
 * Both serve.php (frontend serving) and save-content.php (backend saving)
 * use the SAME walker on the SAME source file, so IDs stay consistent.
 */

function load_html_dom($html) {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = true;
    $dom->formatOutput = false;
    // Force UTF-8
    $wrapped = '<?xml encoding="UTF-8">' . $html;
    $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    return $dom;
}

/**
 * Walk all elements in document order. Returns array of DOMElement nodes.
 * Skips <script>, <style>, <head> children except <title>.
 */
function walk_elements(DOMDocument $dom) {
    $nodes = [];
    $iter = function($el) use (&$iter, &$nodes) {
        if (!($el instanceof DOMElement)) return;
        $tag = strtolower($el->tagName);
        // Skip non-content elements entirely (don't even descend)
        if (in_array($tag, ['script', 'style', 'meta', 'link', 'noscript'])) return;
        $nodes[] = $el;
        foreach ($el->childNodes as $child) {
            if ($child instanceof DOMElement) $iter($child);
        }
    };
    $root = $dom->documentElement;
    if ($root) {
        foreach ($root->childNodes as $c) {
            if ($c instanceof DOMElement) $iter($c);
        }
    }
    return $nodes;
}

/**
 * Assign data-ie-id attributes to all walkable elements.
 * Returns serialized HTML.
 */
function inject_editor_ids($html) {
    $dom = load_html_dom($html);
    $nodes = walk_elements($dom);
    $i = 0;
    foreach ($nodes as $node) {
        $node->setAttribute('data-ie-id', (string)$i);
        $i++;
    }
    return serialize_dom($dom);
}

/**
 * Build a map of [ie-id => DOMElement] for the given DOM (without modifying it).
 */
function build_id_map(DOMDocument $dom) {
    $nodes = walk_elements($dom);
    $map = [];
    foreach ($nodes as $i => $n) $map[(string)$i] = $n;
    return $map;
}

function serialize_dom(DOMDocument $dom) {
    $out = $dom->saveHTML();
    $out = preg_replace('/<\?xml[^>]+\?>\s*/', '', $out);
    return $out;
}
