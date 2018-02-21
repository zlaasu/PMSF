<?php
$full_gym = isset($_GET['full_gym']) ? $_GET['full_gym'] : '';
$short_gym = isset($_GET['short_gym']) ? $_GET['short_gym'] : '';
?>
<html>
<head>
<style>
table.gyms {
	border-spacing: 0;
}
table.gyms th td {
	font-weight: bold;
}
table.gyms td {
	border: solid 1px gray;
}
img {
	height: 128px;
}
</style>
</head>
<body>
<form action="gymy.php" method="get">
<table>
<tr>
<td>Podaj pełną nazwę gyma</td>
<td><input type="text" name="full_gym" value="<?php echo $full_gym ?>" /></td>
</tr><tr>
<td>lub niepełną nazwę gyma (minimum 4 znaki)</td>
<td><input type="text" name="short_gym" value="<?php echo $short_gym ?>" /></td>
</tr>
</table>
<input type="submit" value="ok" />
</form>
<?php
include('config/config.php');
if (!empty($full_gym) || (!empty($short_gym) && strlen($short_gym) > 3)) {
	$params = array();
	$where = '';
	if (!empty($full_gym)) {
		$params[':full_gym'] = $full_gym;
		$where = ' AND name = :full_gym';
	}
	if (!empty($short_gym) && strlen($short_gym) > 3) {
		$params[':short_gym'] = "%" . strtolower($short_gym) . "%";
		$where = " AND lower(name) like :short_gym";
	}
	$where = substr($where, 4);
	$gyms = $db->query("select name, description, url, latitude, longitude from gymdetails gd join gym g on g.gym_id = gd.gym_id where $where limit 100", $params)->fetchAll(\PDO::FETCH_ASSOC);
	if (count($gyms)) {
		?>
		<table class="gyms">
			<tr>
				<th>Nazwa</th>
				<th>Opis</th>
				<th>Lokalizacja</th>
				<th>Miniatura</th>
			</tr>
		<?php
		foreach ($gyms as $gym) {
			?>
			<tr>
				<td><?php echo $gym['name'] ?></td>
				<td><?php echo $gym['description'] ?></td>
				<td><a href="http://maps.google.com/maps?q=<?php echo $gym['latitude'] . "," . $gym['longitude'] ?>">Google Maps</a></td>
				<td><a href="<?php echo $gym['url'] ?>"><img src="<?php echo $gym['url'] ?>" /></a></td>
			</tr>
			<?php
		}
		echo "</table>";
	} else {
		echo "<b>Brak wyników</b>";
	}
}
