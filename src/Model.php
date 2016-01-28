<?php

class Model
{
    protected $pdo;

    public function __construct(array $config)
    {
        try {
            if ($config['engine'] == 'mysql') {
                $this->pdo = new \PDO(
                    'mysql:dbname='.$config['database'].';host='.$config['host'],
                    $config['user'],
                    $config['password']
                );
                $this->pdo->exec('SET CHARSET UTF8');
            } else {
                $this->pdo = new \PDO(
                    'sqlite:'.$config['file']
                );
            }
        } catch (\PDOException $error) {
            throw new ModelException('Unable to connect to database');
        }
    }

    /**
     * Tries to execute a statement, throw an explicit exception on failure
     */
    protected function execute(\PDOStatement $query, array $variables = array())
    {
        if (!$query->execute($variables)) {
            $errors = $query->errorInfo();
            throw new ModelException($errors[2]);
        }

        return $query;
    }

    /**
     * Inserting a book in the database
     */
    public function insertBook($title, $author, $synopsis, $image, $copies)
    {
        $query = $this->pdo->prepare('INSERT INTO livres (titre, auteur, synopsis, image)
            VALUES (?, ?, ?, ?)');
        $this->execute($query, array($title, $author, $synopsis, $image));
        // TODO: Créer $copies exemplaires
        $lastid = $this->pdo->prepare('SELECT LAST_INSERT_ID() FROM livres');
        $this->execute($lastid);
        $lastid = $lastid->fetch();

        for ($i = 0; $i < $copies; $i++) {
        $query = $this->pdo->prepare('INSERT INTO exemplaires (book_id) VALUES (:idbook)');
        $this->execute($query, array(":idbook" => $lastid[0]));
        }
        
        /**
          * Getting exemplaires of books id
        */
        public function getNbreExemplaires($idBook){
             $query = $this->pdo->prepare('SELECT COUNT(book_id) FROM exemplaires WHERE book_id = :idBook');
             $query->execute(array(":idBook" => $idBook));
             return $query->fetch();
          }

    /**
     * Getting all the books
     */
    public function getBooks()
    {
        $query = $this->pdo->prepare('SELECT livres.* FROM livres');
        $this->execute($query);
        return $query->fetchAll();
    }

    /**
     * Getting book form id
     */
    public function getBook($id)
    {
        $query = $this->pdo->prepare('SELECT livres.* FROM livres WHERE livres.id = :idlivres');
        $query->execute(array(":idlivres" => $id));
        return $query->fetchAll();
    }
    /*
    * setter Emprunt
    */
     public function setEmprunt($nom, $dateFin, $idEx)
     {
        $query = $this->pdo->prepare('INSERT INTO emprunts(personne, exemplaire, debut, fin) VALUES (?,?,?,?)');
        $this->execute($query,array($nom, $idEx, date("Y-m-d H:i:s"), $dateFin));
     }

     public function getEmprunte($idBook){
        $query = $this->pdo->prepare('SELECT emprunts.exemplaire FROM emprunts INNER JOIN exemplaires
        ON emprunts.exemplaire = exemplaires.id WHERE :idbook = exemplaires.book_id');
        $query->execute(array(":idbook" => $idbook ));
        return $query->fetchAll();
     }

    /**
    *  Getting for example books
    */
    public function getExemplaires($idBook){
        $query = $this->pdo->prepare('SELECT exemplaires.* FROM exemplaires WHERE book_id = :idbook');
        $query ->execute(array(":idbook" => $idbook));
        return $query->fetchAll();
    }

}
