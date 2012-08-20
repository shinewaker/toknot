<?php 
$this->out_html.=<<<XTHTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head><meta content="text/html; charset=utf-8" http-equiv="Content-Type">
<title>{$this->_var->site_name}</title>

XTHTML;
$this->inc_css("style");
$this->out_html.=<<<XTHTML

<noscript>
<style type="text/css">div {display: none;} table {display: none;} #noscript {padding: 3em; font-size: 130%;color:#000;}</style>
<p id="noscript">要使用Dopush，必须启用 JavaScript。不过，JavaScript 似乎已被禁用或者您的浏览器不支持 JavaScript。要使用Dopush，请更改您的浏览器选项，启用 JavaScript，然后 <a href="/">重试</a>。</p></noscript>

XTHTML;
$this->inc_js("x");
$this->out_html.=<<<XTHTML


XTHTML;
$this->inc_js("ui");
$this->out_html.=<<<XTHTML

</head>
<body>
<div class="m-block">
<div class="b-center" id="b-center">
</div>
<script type="text/javascript">
X.ready(dop.init);
</script>
<div class="b-footer">
{$this->_var->copyright}
{$this->_var->__X_RUN_TIME__}
</div>
</div>
</body>
</html>

XTHTML;