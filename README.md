# phpToUnderscore
Convert php to template underscore


## Using

new PhpConver( file_get_contents( 'html.tpl.php' ), false, 'put.php' );


## From Template PHP
<div id="setting" class="awe-panel__box-square-item">
    <span class="title"><?php echo !isset($a['asdf']) ? $a['asdf'] : '123'; ?></span>
    <span><?php echo _e('test function','awecontent') ?></span>
    <span><?php __('test function','awecontent') ?></span>
    <span><?php echo esc_attr('123') ?></span>
</div>

## To Template Underscore

<div id="setting" class="awe-panel__box-square-item">\ 
    <span class="title"><%= (typeof a['asdf'] == "undefined") ? a['asdf'] : '123' %></span>\ 
    <span><%= _e('test function','awecontent') %></span>\ 
    <span><%   %></span>\ 
    <span><%= esc_attr('123') %></span>\ 
</div>