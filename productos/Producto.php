<?php

    // Clase entidad Producto
    class Producto {

        private $id;
        private $id_categoria;
        private $id_subcategoria;
        private $nombre;
        private $descripcion;
        private $precio;
        private $stock;
        private $imagen;
        private $estado;
        private $categoriaNombre;
        private $subcategoriaNombre;

        public function getId() {
            return $this->id;
        }

        public function getIdCategoria() {
            return $this->id_categoria;
        }

        public function getIdSubcategoria() {
            return $this->id_subcategoria;
        }

        public function getNombre() {
            return $this->nombre;
        }

        public function getDescripcion() {
            return $this->descripcion;
        }

        public function getPrecio() {
            return $this->precio;
        }

        public function getStock() {
            return $this->stock;
        }

        public function getImagen() {
            return $this->imagen;
        }

        public function getEstado() {
            return $this->estado;
        }

        public function getCategoriaNombre() {
            return $this->categoriaNombre;
        }

        public function getSubcategoriaNombre() {
            return $this->subcategoriaNombre;
        }
    }

    // Obtener productos con paginación y ordenación
    function obtenerProductos($con, $pagina, $resultadosPP, $orden, $tipoOrden) {

        $inicio = ($pagina - 1) * $resultadosPP;

        // Columnas permitidas para ordenar
        $columnasPermitidas = ["p.id", "p.nombre", "p.precio", "p.stock", "c.nombre", "s.nombre"];
        if (!in_array($orden, $columnasPermitidas)) $orden = "p.id";
        $tipoOrden = strtoupper($tipoOrden) === "DESC" ? "DESC" : "ASC";

        $sql = $con->prepare("SELECT p.id, p.id_categoria, p.id_subcategoria, p.nombre, p.descripcion, p.precio, p.stock, p.imagen, p.estado, c.nombre AS categoriaNombre, 
                              s.nombre AS subcategoriaNombre FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id LEFT JOIN subcategorias s ON p.id_subcategoria = s.id
                              ORDER BY $orden $tipoOrden LIMIT :inicio, :resultados");

        $sql->bindParam(":inicio", $inicio, PDO::PARAM_INT);
        $sql->bindParam(":resultados", $resultadosPP, PDO::PARAM_INT);
        $sql->execute();

        $sql->setFetchMode(PDO::FETCH_CLASS, "Producto");
        return $sql->fetchAll();
    }

?>
