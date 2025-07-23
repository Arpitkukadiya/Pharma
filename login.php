<?php

@include 'config.php';

session_start();

if(isset($_POST['submit'])){

   $email = $_POST['email'];
   $email = filter_var($email, FILTER_SANITIZE_STRING);
   $pass = md5($_POST['pass']);
   $pass = filter_var($pass, FILTER_SANITIZE_STRING);

   $sql = "SELECT * FROM `users` WHERE email = ? AND password = ?";
   $stmt = $conn->prepare($sql);
   $stmt->execute([$email, $pass]);
   $rowCount = $stmt->rowCount();  

   $row = $stmt->fetch(PDO::FETCH_ASSOC);

   if($rowCount > 0){

      if($row['user_type'] == 'admin'){

         $_SESSION['admin_id'] = $row['id'];
         header('location:admin_page.php');

      }elseif($row['user_type'] == 'user'){

         $_SESSION['user_id'] = $row['id'];
         header('location:home.php');

      }else{
         $message[] = 'no user found!';
      }

   }else{
      $message[] = 'incorrect email or password!';
   }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <style>
     <style>
   
     body {
            font-family: Arial, sans-serif;
            background-image: url('food1.webp'); /* Replace 'your-background-image.jpg' with the actual image URL */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            text-align: center;
            margin-top: 50px;
            color: #333; /* Text color */
        }
        .vk1{
width:100%;
position: absolute;
z-index: -2;
}

p{
font-color:blue;
}
        form {
            max-width: 300px;
            margin: 0 auto;
            background-color: rgba(255, 252, 255, 0.8); /* Semi-transparent white background */
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 0em;
            /*this is css for center a form */
            margin-top: 2rem;
   font-size: 2.2rem;

   align-items: center;
  
}
        

        h3 {
            color: #105ee7; /* Heading color */
        }

        .box {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }

        .btn {
            background-color: #105ee7; /* Blue login button */
            color: #fff;
            padding: 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        
        a {
            text-decoration: none;
            color: #205fd2;
        }

        .message {
            background-color: #ff9999; /* Light red background for messages */
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            color: #fff;
            display: inline-block;
        }

        .message i {
            cursor: pointer;
        }  
     
    </style>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>login</title>

</head>
<body>
<img class="vk1" src="12.jpg" alt=
"foodzone1">
<?php

if(isset($message)){
   foreach($message as $message){
      echo '
      <div class="message">
         <span>'.$message.'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}
?>
<section class="form-container">
<center>
   <form action="" method="POST">
      <h3>login now</h3>

      <input type="email" name="email" class="box" placeholder="enter your email" required>
      <input type="password" name="pass" class="box" placeholder="enter your password" required>
          <input type="submit" value="login now" class="btn" name="submit">
          <h4> <p>don't have an account? <a href="register.php">register now</a></p></h4>
   </form>
   </section>
</body>
</html>