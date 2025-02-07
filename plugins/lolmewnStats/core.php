<?php

class lolmewnStats extends MySQLplugin
{
    public $pluginName = "lolmewnStats";
    public $stats;

    public $plugin = array(
        "idColumn" => "uuid",
        "playerNameColumn" => "name",
        "UUIDcolumn" => "uuid",
        "indexTable" => "players",
        "UUIDisID" => true,
        "singleTable" => false,
        "valueColumn" => "value",
        "tables" => [],
        "defaultPrefix" => "Stats_"
    );

    public function onLoad()
    {
        $this->config->setDefault("stats", array(
            "arrows" => "Arrows Shot",
            "beds_entered" => "Beds Entered",
            "blocks_broken" => "Blocks Broken",
            "blocks_placed" => "Blocks Placed",
            "buckets_emptied" => "Buckets Emptied",
            "buckets_filled" => "Buckets Filled",
            "commands_done" => "Commands Done",
            "damage_taken" => "Damage Taken",
            "death" => "Times Died",
            "eggs_thrown" => "Eggs Thrown",
            "fish_caught" => "Fish Caught",
            "items_crafted" => "Items Crafted",
            "items_dropped" => "Items Dropped",
            "items_picked_up" => "Items Picked Up",
            "joins" => "Joins",
            "kill" => "Kills",
            "last_join" => "Last Joined",
            "last_seen" => "Last Seen",
            "move" => "Blocks Traversed",
            "omnomnom" => "Food Eaten",
            "playtime" => "Play Time",
            "pvp" => "PvP Kills",
            "shears" => "Times striped a sheep",
            "teleports" => "Teleports",
            "times_changed_world" => "Worlds Changed",
            "times_kicked" => "Times Kicked",
            "tools_broken" => "Tools Broken",
            "trades" => "Trades",
            "votes" => "Votes",
            "words_said" => "Words Said",
            "xp_gained" => "Xp Gained",
        ));
        $this->stats = $this->config->get("stats");
        foreach ($this->stats as $key => $value) {
            array_push($this->plugin["tables"], $key);
        }

    }

    public function statName($stat)
    {
        if (isset($this->stats[$stat]))
            return $this->stats[$stat];
        else
            return $stat;
    }

    public function getAllPlayerStats($stat, $limit = 0)
    {

        $stmt = $this->mysqli->stmt_init();

        if ($stat == "last_join" || $stat == "last_seen") {
            $sql = "SELECT *, min(value) as value FROM {$this->prefix}{$stat} INNER JOIN `{$this->prefix}players` on {$this->prefix}{$stat}.UUID = {$this->prefix}players.UUID GROUP BY {$this->prefix}{$stat}.UUID ORDER BY value Desc";
        } else {
            $sql = "SELECT *, sum(value) as value FROM {$this->prefix}{$stat} INNER JOIN `{$this->prefix}players` on {$this->prefix}{$stat}.UUID = {$this->prefix}players.UUID GROUP BY {$this->prefix}{$stat}.UUID ORDER BY value Desc";
        }


        if ($limit != 0) {
            $sql = $sql . " LIMIT $limit";
        }

        if ($stmt->prepare($sql)) {
            $stmt->execute();
            $output = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $output ?: [];
        }
        return [];
    }

    public function getStatSum($stat)
    {

        $stmt = $this->mysqli->stmt_init();

        if ($stat == "last_join" || $stat == "last_seen") {
            return "";
        }
        $sql = "SELECT sum(value) as value FROM {$this->prefix}{$stat}";
        if ($stmt->prepare($sql)) {
            $stmt->execute();
            $stmt->bind_result($output);
            $stmt->fetch();
            $stmt->close();
            return $output ?: "";
        }
        return [];
    }

    public function getStat($stat, $player, $group = TRUE)
    {
        // TODO Clean up getStat function
        $stmt = $this->mysqli->stmt_init();
        if ($group) {
            if ($stat == "last_join" || $stat == "last_seen") {
                $sql = "SELECT min(value) as value FROM {$this->prefix}{$stat} WHERE uuid=?";
            } else {
                $sql = "SELECT sum(value) as value FROM {$this->prefix}{$stat} WHERE uuid=?";
            }
        } else {
            $sql = "SELECT * FROM {$this->prefix}{$stat} WHERE uuid=?";
        }

        if ($group)
            $sql = $sql . " GROUP BY uuid";

        if ($stmt->prepare($sql)) {
            $stmt->bind_param("s", $player);
            $stmt->execute();
            $output = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            if ($group) {
                if (isset($output['value'])) {
                    $output = $output['value'];
                } else {
                    if (isset($output[0])) {
                        $output = $output[0]['value'];
                    }
                }
            }

            $stmt->close();
            if ($stat == "last_join" || $stat == "last_seen") {
                if (!empty($output)) {
                    $time = time() - ($output / 1000);
                    $output = secondsToTime(round($time)) . " ago";
                } else {
                    $output = "Never";
                }
            } elseif ($stat == "playtime") {
                $output = secondsToTime($output);
            }
            return $output;
        }
        return false;
    }

    public function getStats($stat, $player, $limit = 0, $extra = "")
    {

        $stmt = $this->mysqli->stmt_init();

        $sql = "SELECT * FROM {$this->prefix}{$stat} WHERE uuid = ?";

        if ($limit != 0) {
            $sql = $sql . ' ' . $extra . " LIMIT $limit";
        } else {
            $sql = $sql . ' ' . $extra;
        }
        if ($stmt->prepare($sql)) {
            $stmt->bind_param("s", $player);
            $stmt->execute();
            $output = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $output;
        }
        return false;
    }

    public function getPlayerBlock($player)
    {

        $stmt = $this->mysqli->stmt_init();

        $sql = "
SELECT a.world, a.name as name, ifnull(a.value, 0) as 'broken', ifnull(b.value, 0) as 'placed' 
FROM {$this->prefix}blocks_broken as a 
LEFT OUTER JOIN {$this->prefix}blocks_placed as b on a.uuid = b.uuid and a.name = b.name and a.world = b.world 
WHERE a.uuid = ? 
GROUP BY a.name, a.world 
UNION ALL 
SELECT d.world, d.name as name, ifnull(c.value, 0) as 'broken', ifnull(d.value, 0) as 'placed' 
FROM {$this->prefix}blocks_broken as c 
RIGHT OUTER JOIN {$this->prefix}blocks_placed as d on c.uuid = d.uuid and c.name = d.name and c.world = d.world 
WHERE d.uuid = ? and c.value is null 
GROUP BY d.name, d.world";


        if ($stmt->prepare($sql)) {
            $stmt->bind_param("ss", $player,$player);
            $stmt->execute();
            $output = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $output;
        }
        return false;
    }
}
