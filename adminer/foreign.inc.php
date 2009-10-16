<?php
$TABLE = $_GET["foreign"];
if ($_POST && !$error && !$_POST["add"] && !$_POST["change"] && !$_POST["change-js"]) {
	if ($_POST["drop"]) {
		query_redirect("ALTER TABLE " . idf_escape($TABLE) . "\nDROP FOREIGN KEY " . idf_escape($_GET["name"]), ME . "table=" . urlencode($TABLE), lang('Foreign key has been dropped.'));
	} else {
		$source = array_filter($_POST["source"], 'strlen');
		ksort($source); // enforce input order
		$target = array();
		foreach ($source as $key => $val) {
			$target[$key] = $_POST["target"][$key];
		}
		query_redirect("ALTER TABLE " . idf_escape($TABLE)
			. (strlen($_GET["name"]) ? "\nDROP FOREIGN KEY " . idf_escape($_GET["name"]) . "," : "")
			. "\nADD FOREIGN KEY (" . implode(", ", array_map('idf_escape', $source)) . ") REFERENCES " . idf_escape($_POST["table"]) . " (" . implode(", ", array_map('idf_escape', $target)) . ")"
			. (in_array($_POST["on_delete"], $on_actions) ? " ON DELETE $_POST[on_delete]" : "")
			. (in_array($_POST["on_update"], $on_actions) ? " ON UPDATE $_POST[on_update]" : "")
		, ME . "table=" . urlencode($TABLE), (strlen($_GET["name"]) ? lang('Foreign key has been altered.') : lang('Foreign key has been created.')));
		$error = lang('Source and target columns must have the same data type, there must be an index on the target columns and referenced data must exist.') . "<br>$error"; //! no partitioning
	}
}

page_header(lang('Foreign key'), $error, array("table" => $TABLE), $TABLE);

$row = array("table" => $TABLE, "source" => array(""));
if ($_POST) {
	$row = $_POST;
	ksort($row["source"]);
	if ($_POST["add"]) {
		$row["source"][] = "";
	} elseif ($_POST["change"] || $_POST["change-js"]) {
		$row["target"] = array();
	}
} elseif (strlen($_GET["name"])) {
	$foreign_keys = foreign_keys($TABLE);
	$row = $foreign_keys[$_GET["name"]];
	$row["source"][] = "";
}

$source = array_keys(fields($TABLE)); //! no text and blob
$target = ($TABLE === $row["table"] ? $source : array_keys(fields($row["table"])));
?>

<form action="" method="post">
<p>
<?php echo lang('Target table'); ?>:
<?php echo html_select("table", array_keys(table_status_referencable()), $row["table"], "this.form['change-js'].value = '1'; this.form.submit();"); ?>
<input type="hidden" name="change-js" value="">
</p>
<noscript><p><input type="submit" name="change" value="<?php echo lang('Change'); ?>"></noscript>
<table cellspacing="0">
<thead><tr><th><?php echo lang('Source'); ?><th><?php echo lang('Target'); ?></thead>
<?php
$j = 0;
foreach ($row["source"] as $key => $val) {
	echo "<tr>";
	echo "<td>" . html_select("source[" . intval($key) . "]", array(-1 => "") + $source, $val, ($j == count($row["source"]) - 1 ? "foreign_add_row(this);" : 1));
	echo "<td>" . html_select("target[" . intval($key) . "]", $target, $row["target"][$key]);
	$j++;
}
?>
</table>
<p>
<?php echo lang('ON DELETE'); ?>: <?php echo html_select("on_delete", array(-1 => "") + $on_actions, $row["on_delete"]); ?>
 <?php echo lang('ON UPDATE'); ?>: <?php echo html_select("on_update", array(-1 => "") + $on_actions, $row["on_update"]); ?>
<p>
<input type="hidden" name="token" value="<?php echo $token; ?>">
<input type="submit" value="<?php echo lang('Save'); ?>">
<?php if (strlen($_GET["name"])) { ?><input type="submit" name="drop" value="<?php echo lang('Drop'); ?>"<?php echo $confirm; ?>><?php } ?>
<noscript><p><input type="submit" name="add" value="<?php echo lang('Add column'); ?>"></noscript>
</form>
