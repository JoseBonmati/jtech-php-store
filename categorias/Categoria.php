<?php

    // Clase entidad Categoría
    class Categoria {

        private $id;
        private $nombre;
        private $estado;

        public function getId() {
            return $this->id;
        }

        public function getNombre() {
            return $this->nombre;
        }

        public function getEstado() {
            return $this->estado;
        }
    }

    // Obtener categorías con paginación y ordenación
    function obtenerCategorias($con, $pagina, $resultadosPP, $orden, $tipoOrden) {

        $inicio = ($pagina - 1) * $resultadosPP;

        // Columnas permitidas para ordenar
        $columnasPermitidas = ["id", "nombre", "estado"];
        if (!in_array($orden, $columnasPermitidas)) $orden = "id";
        $tipoOrden = strtoupper($tipoOrden) === "DESC" ? "DESC" : "ASC";

        $query = $con->prepare("SELECT id, nombre, estado FROM categorias ORDER BY $orden $tipoOrden LIMIT :inicio, :resultados");
        $query->bindParam(":inicio", $inicio, PDO::PARAM_INT);
        $query->bindParam(":resultados", $resultadosPP, PDO::PARAM_INT);
        $query->execute();

        // Mapear resultados a la clase Categoria
        $query->setFetchMode(PDO::FETCH_CLASS, "Categoria");
        return $query->fetchAll();
    }

?>
