## 🖥 Requirements

The following tools are required in order to start the installation.

* PHP 8.1.0 or greater.
* Database (eg: MySQL, MariaDB)
* Web Server (eg: Nginx, Apache)
* Local Development (Valet for Mac or Laragon for Windows)

## 🚀 Installation

1. Clone the repository with `git clone`
2. Copy __.env.example__ file to __.env__ and edit database credentials there

    ```shell
    cp .env.example .env
    ```
   
3. Install composer packages

    ```shell
    composer install
    ```
   
4. Install npm packages and compile files

    ```shell
    npm install
    ```
   
    For **Development**:
    ```shell
    npm run dev
    ```
   
    For **Production**:
    ```shell
    npm run build
    ```
   
5. Generate `APP_KEY` in **.env**

    ```shell
    php artisan key:generate
    ```
   
6. Running migrations and all database seeds

    ```shell
    php artisan migrate --seed
    ```
   
7. Create the symbolic link

    ```shell
    php artisan storage:link
    ```

8. Set up a working e-mail driver like [Mailrap](https://mailtrap.io/) or use [MailHog](https://github.com/mailhog/MailHog) (local)
9. Running horizon for queue

    ```shell
    php artisan horizon
    ```

You can now visit the app in your browser by visiting [https://borang-v3.test](http://borang-v3.test). If you seeded the database you can log in into a test account with **`admin@admin.com`** & **`password`**

## 🎨 Template HTML

1. Velzon by Themesbrand
   - [Demo](https://themesbrand.com/velzon/html/default/index.html)
   - [Documentation](https://themesbrand.com/velzon/docs/html/index.html)
