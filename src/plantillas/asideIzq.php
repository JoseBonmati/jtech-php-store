    <aside class="aside-izquierdo">

        <?php

            // Obtener categorías activas
            $catQuery = $con->prepare("SELECT id, nombre FROM categorias WHERE estado = 'activo' ORDER BY nombre ASC");
            $catQuery->execute();
            $categorias = $catQuery->fetchAll(PDO::FETCH_ASSOC);

        ?>

        <div class="accordion accordion-flush" id="accordionCategorias">

            <?php foreach ($categorias as $cat): ?>
                <?php
                    // Obtener subcategorías activas de esta categoría
                    $subQuery = $con->prepare("SELECT id, nombre FROM subcategorias  WHERE id_categoria = :id AND estado = 'activo' ORDER BY nombre ASC");
                    $subQuery->execute([":id" => $cat["id"]]);
                    $subcategorias = $subQuery->fetchAll(PDO::FETCH_ASSOC);

                    // IDs únicos para el acordeón
                    $headingId = "heading-" . $cat["id"];
                    $collapseId = "collapse-" . $cat["id"];
                ?>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="<?= $headingId ?>">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" 
                        aria-expanded="false" aria-controls="<?= $collapseId ?>"><?= htmlspecialchars($cat["nombre"]) ?>
                        </button>
                    </h2>

                    <div id="<?= $collapseId ?>" class="accordion-collapse collapse" aria-labelledby="<?= $headingId ?>" data-bs-parent="#accordionCategorias">
                        <div class="accordion-body p-0">
                            <?php if (count($subcategorias) > 0): ?>
                                <ul class="list-group list-group-flush">
                                <!-- Ver todo -->
                                <li class="list-group-item jtech-subitem jtech-clickable" onclick="window.location='index.php?categoria=<?= $cat['id'] ?>'">
                                    <a href="index.php?categoria=<?= $cat['id'] ?>" class="text-decoration-none text-dark fw-semibold d-block">
                                        Ver todo
                                    </a>
                                </li>

                                <!-- Subcategorías -->
                                <?php foreach ($subcategorias as $sub): ?>
                                    <li class="list-group-item jtech-subitem jtech-clickable" onclick="window.location='index.php?subcategoria=<?= $sub['id'] ?>'">
                                        <a href="index.php?subcategoria=<?= $sub['id'] ?>" class="text-decoration-none text-dark d-block">
                                            <?= htmlspecialchars($sub["nombre"]) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <?php else: ?>
                                <p class="text-muted text-center py-2">No hay subcategorías</p>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

            <?php endforeach; ?>

        </div>
    </aside>
