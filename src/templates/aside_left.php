    <aside class="sidebar-left">

        <?php
            // Fetch active categories
            $categoryStmt = $db->prepare("SELECT id, nombre FROM categorias WHERE estado = 'activo' ORDER BY nombre ASC");
            $categoryStmt->execute();
            $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <div class="accordion accordion-flush" id="categoriesAccordion">

            <?php foreach ($categories as $category): ?>
                <?php
                    // Fetch active subcategories for this category
                    $subCategoryStmt = $db->prepare("SELECT id, nombre FROM subcategorias WHERE id_categoria = :id AND estado = 'activo' ORDER BY nombre ASC");
                    $subCategoryStmt->execute([":id" => $category["id"]]);
                    $subcategories = $subCategoryStmt->fetchAll(PDO::FETCH_ASSOC);

                    // Unique IDs for the accordion
                    $headingId = "heading-" . $category["id"];
                    $collapseId = "collapse-" . $category["id"];
                ?>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="<?= $headingId ?>">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" 
                        aria-expanded="false" aria-controls="<?= $collapseId ?>"><?= htmlspecialchars($category["nombre"]) ?>
                        </button>
                    </h2>

                    <div id="<?= $collapseId ?>" class="accordion-collapse collapse" aria-labelledby="<?= $headingId ?>" data-bs-parent="#categoriesAccordion">
                        <div class="accordion-body p-0">
                            <?php if (count($subcategories) > 0): ?>
                                <ul class="list-group list-group-flush">
                                
                                <li class="list-group-item jtech-subitem jtech-clickable" onclick="window.location='/index.php?category=<?= $category['id'] ?>'">
                                    <a href="/index.php?category=<?= $category['id'] ?>" class="text-decoration-none text-dark fw-semibold d-block">
                                        Ver todo
                                    </a>
                                </li>

                                <?php foreach ($subcategories as $subcategory): ?>
                                    <li class="list-group-item jtech-subitem jtech-clickable" onclick="window.location='/index.php?subcategory=<?= $subcategory['id'] ?>'">
                                        <a href="/index.php?subcategory=<?= $subcategory['id'] ?>" class="text-decoration-none text-dark d-block">
                                            <?= htmlspecialchars($subcategory["nombre"]) ?>
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