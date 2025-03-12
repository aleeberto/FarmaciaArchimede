<?php

require_once($root ."/php/script/prodottoBuilder.php");

class Builder {

    private string $base_path;

    public function __construct(string $base_path = "") {
        $this->base_path = realpath(__DIR__ . "/../" . $base_path);
    }
    
    public function load_template(string $name): Template {
        $template_content = file_get_contents("{$this->base_path}/{$name}");
        return new Template($name, $template_content);
    }
}

class Template {

    private const PATT_BEGIN = '<component>';
    private const PATT_END = '</component>';

    private string $template_name;
    private string $state;

    public function __construct(string $template_name, string $state) {
        $this->template_name = $template_name;
        $this->state = $state;
    }

    public function insert(string $id, string $value): void {
        $patt_begin = self::PATT_BEGIN;
        $patt_end = self::PATT_END;

        $n_changes = 0;
        $patt = "{$patt_begin}{$id}{$patt_end}";
        $this->state = str_replace($patt, $value, $this->state, $n_changes);
    }

    public function insert_all(array $parameters): void {
        foreach ($parameters as $id => $value) {
            $this->insert($id, $value);
        }
    }

    public function build(): string {
        $patt_begin = self::PATT_BEGIN;
        $patt_end = self::PATT_END;

        $patt = "~{$patt_begin}([a-zA-Z0-9_-]+){$patt_end}~";
        $matches = array();
        preg_match_all($patt, $this->state, $matches);
        return $this->state;
    }

}


class Database {
    private string $url;
    private string $user;
    private string $password;
    private string $database;
    private ?mysqli $connection;
    
    private static ?Database $instance = null;

    private function __construct(string $url, string $user, string $password, string $database) {
        $this->url = $url;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
        $this->connection = null;
    }

    public static function getInstance(string $url, string $user, string $password, string $database): Database {
        if (self::$instance === null) {
            self::$instance = new Database($url, $user, $password, $database);
        }
        return self::$instance;
    }

    public function connect(): mysqli {
        if ($this->connection === null) {
            $this->connection = new mysqli($this->url, $this->user, $this->password, $this->database);

            if ($this->connection->connect_error) {
                throw new Exception("Errore di connessione al Database: " . $this->connection->connect_error);
            }
        }
        return $this->connection;
    }
}

$db = Database::getInstance("farmacia_mysql", "root", "root_password", "farmacia_archimede");

$conn = $db->connect();

$builder = new Builder("templates");

//Append head

function build_head(): string {
    global $builder;

    $head_template = $builder->load_template("head.html");
    return $head_template->build();
}

// Funzione di Build del footer

function build_footer(): string {
    global $builder;

    $header_template = $builder->load_template("footer.html");
    return $header_template->build();
}

// Funzione di Build del header qui inserirò i link alle pagine e i nomi

$pages = array(
    array("/index.php", "Home"), 
    array("/php/pages/prodotti.php", "I Nostri Prodotti"), 
    array("/php/pages/chisiamo.php", "Chi Siamo"),
    array("/php/pages/contatti.php", "Contatti"), 
    array("/php/pages/areapersonale.php", "Area Personale")
);

function build_header(): string {
    global $builder;
    global $page_index;
    global $pages;

    $header_template = $builder->load_template("header.html");

    
    $pages_len = count($pages);
    foreach ($pages as $i => $page) {
        $id = "page{$i}";
        $element = "";
        if ($i === $page_index) {
            $element = make_list_item($page[1], "active");
        } else {
            $element = make_list_item(make_link($page[1], $page[0]));
        }
        $header_template->insert($id, $element);
    }

    $page = " ";

    return $header_template->build();
}

function make_list_item(string $content, ?string $class = null): string {
    $open = "<li>";
    if ($class !== null) {
        $open = "<li class=\"{$class}\">";
    }
    return "{$open}{$content}</li>";
}

function make_link(string $content, string $ref, ?string $class = null): string {
    $open = "<a";
    if ($class !== null) {
        $open .= " class=\"{$class}\"";
    }
    return "{$open} href='{$ref}'>{$content}</a>";
}

?>