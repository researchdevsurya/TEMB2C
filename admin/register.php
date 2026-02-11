<?php
require '../db.php';


$error=""; $success="";

if($_SERVER['REQUEST_METHOD']=="POST"){
    $email=$_POST['email'];
    $password=$_POST['password'];
    $key=$_POST['key'];

    if($key!="777"){
        $error="Invalid Admin Key";
    } else {
        $hash=password_hash($password,PASSWORD_DEFAULT);

        try{
            $pdo->prepare("INSERT INTO admins(email,password) VALUES(?,?)")
                ->execute([$email,$hash]);
            $success="Admin Created. Login Now.";
        }catch(Exception $e){
            $error="Email already exists";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white p-8 rounded-xl shadow w-96">
<h2 class="text-xl font-bold mb-4">Admin Register</h2>

<?php if($error): ?><div class="text-red-500"><?= $error ?></div><?php endif;?>
<?php if($success): ?><div class="text-green-500"><?= $success ?></div><?php endif;?>

<form method="POST" class="space-y-3">
<input name="email" placeholder="Email" required class="w-full p-3 border rounded">
<input name="password" type="password" placeholder="Password" required class="w-full p-3 border rounded">
<input name="key" placeholder="Admin Secret Key" required class="w-full p-3 border rounded">

<button class="w-full bg-blue-600 text-white p-3 rounded">Create Admin</button>
</form>
</div>
</body>
</html>
