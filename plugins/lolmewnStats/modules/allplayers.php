<?php
if (!config::configExist("stat","MODULE_allplayers_lolmewnStats")){
	config::set("stat","playtime","MODULE_allplayers_lolmewnStats");
}
?>
<table class="table" id="allPlayers">
	<thead>
		<tr>
			<th>Name</th>
			<th><?=config::get("stat","MODULE_allplayers_lolmewnStats")?></th>
			<th>Sortable</th>
		</tr>
	</thead>
	<tbody>
		<?php
		$statName = config::get("stat","MODULE_allplayers_lolmewnStats");
		foreach ($plugin->getStats($statName) as $stat){
			if ($statName=="playtime"){
				$statDisplay = secondsToTime($stat["value"],$contract=true);
			}else{
				$statDisplay = $stat["value"];
			}
			
			echo "
			<tr>
				<td>
					{$stat["name"]}
				</td>
				<td>
					".$statDisplay."
				</td>
				<td>
					{$stat["value"]}
				</td>
			</tr>";
		}
		?>
	</tbody>
</table>
<script>
	$(document).ready(function() {
	    $('#allPlayers').DataTable({
		    "aoColumnDefs": [
		      { "iDataSort": 2, "aTargets": [ 1 ] },
		      { "bVisible": false, "aTargets": [ 2 ] }
		    ],
	    });
	} );
</script>
