# JTECH PHP Store

This project is a custom-built, full-stack eCommerce platform developed with PHP 8 (OOP), MySQL, and Bootstrap 5. It provides a complete online shopping experience, featuring a dynamic product catalog, a robust shopping cart, and a secure checkout process integrated directly with the Stripe API.

The architecture follows strict Object-Oriented Programming (OOP) principles, utilizing `PDO::FETCH_CLASS` for seamless entity hydration, Singleton patterns for database connections, and a secure Role-Based Access Control (RBAC) system to separate regular customers from store administrators and employees.

## Project Structure

- `database/`: 
  - `jtech_db.sql`: The initialization script containing the complete relational database schema and baseline data.
- `src/`: The main application source code directory.
  - `assets/`: Contains global stylesheets (`style.css`), branding icons, and dynamic product image uploads.
  - `cart/`: Manages the session-based shopping cart logic, including adding, updating quantities, and removing items.
  - `categories/` & `subcategories/`: OOP models and CRUD interfaces for managing the multi-level product catalog hierarchy.
  - `checkout/`: Handles the final purchasing flow, verifying user shipping details, and processing the callbacks from the payment gateway.
  - `orders/`: Contains the `Order` entity, the logic to transition cart sessions into immutable order histories, and the status management panel.
  - `products/`: Core catalog management. Includes the `Product` entity, image upload sanitization, and secure, paginated, and sortable listing views.
  - `reports/`: Generates analytical dashboards for administrators, displaying total sales, top-selling products, and monthly revenue trends.
  - `stripe/`: Houses the official Stripe PHP SDK, the environment variable loader, and the dynamic checkout session creator.
  - `templates/`: Reusable UI components (headers, footers, navigation bars) to maintain DRY principles across the frontend.
  - `users/`: Handles authentication (login/registration), session management, and profile editing.
  - `utils/`: Contains the core `Database.php` (PDO Singleton connection) and routing for the administrative control panel.
  - `index.php`: The storefront's main entry point and product showcase.
- `.env.example`: Template for required environment variables (e.g., Stripe API keys).
- `docker-compose.yaml` & `Dockerfile`: Fully containerized setup integrating the PHP/Apache server with the MySQL database environment.

## Features

- **Object-Oriented Data Hydration**: Leverages advanced PDO fetch modes (`PDO::FETCH_CLASS`) paired with SQL aliasing to directly map relational database rows into strictly typed PHP entity objects (e.g., `Product`, `Order`, `Report`).
- **Role-Based Access Control (RBAC)**: Implements strict session checks across all routes, explicitly dividing capabilities between `administrador`, `empleado`, and `usuario`.
- **Secure Stripe Integration**: Utilizes the official Stripe PHP SDK for payment processing, abstracting sensitive transaction data while dynamically calculating callback URLs based on the active host environment (Local vs. Production).
- **Hardened Security Measures**: Protects against SQL Injections using strict PDO Prepared Statements and hardcoded whitelist mapping arrays for all dynamic `ORDER BY` sorting operations.
- **Dynamic File Management**: Securely handles product image uploads with size limitations, MIME type validation, and dynamic absolute path resolution for reliable rendering across all subdirectories.
- **Environment Agnostic**: Uses custom `.env` parsing logic to keep sensitive API keys and database credentials out of the source code, allowing seamless transitions between local Docker development and remote hosting.

## Running the Project

This application is fully containerized for easy deployment and testing.

1. Clone the repository and navigate to the project root.
2. Duplicate the `.env.example` file, rename it to `.env`, and insert your active Stripe Secret Key and DB config.
3. Build and start the Docker containers:
    ```bash
    docker compose up --build -d
    ```
4. Access the storefront by navigating to http://localhost (or your configured port) in your web browser.