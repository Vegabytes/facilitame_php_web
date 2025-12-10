<?php
class Category {

    public function __construct()
    {

    }

    public static function getNames()
    {
        global $pdo;

        $query = "SELECT id, name FROM `categories` WHERE 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return $categories;
    }

    public static function getIcons()
    {
        return [
            1 => "umbrellaOutline",
            2 => "umbrellaOutline",
            3 => "umbrellaOutline",
            4 => "umbrellaOutline",
            9 => "umbrellaOutline",
            15 => "flashOutline",
            16 => "callOutline",
            17 => "shieldOutline",
            18 => "carOutline"
        ];
    }
}
?>