# billiard-club-backend
**Using Sysmfony**

**Step to run project:**

*Start MAMP or XAMPP*

**1. Clone project**

`git clone https://github.com/projectWeb1002/billiard-club-backend.git`

**2. Install the necessary libraries**

`composer install`

***In the file .env***

*If you are use XAMPP:* 

*Change from:*

 > DATABASE_URL="mysql://root:root@127.0.0.1:3306/billiard_club"> 


*to:*

> DATABASE_URL="mysql://root:@127.0.0.1:3306/billiard_club">
 
 You also need to specify your MySQL port: `3306` , `8889`,...
 
**3. Creat database**

`php bin/console doctrine:database:create`

**4. Migrate the migrations** 

`php bin/console doctrine:migrations:migrate`

**5. Start symfony server**

`symfony serve`

**6. Test the API**
