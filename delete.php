<?php
$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'digital_document_repository';


$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) {
  die("Connection failed: " . mysqli_connect_error());
}


   
$fid = (int)$_GET['id']; 
$sql = "delete from documents where document_id='$fid'";


if (mysqli_query($conn, $sql)) {
    header("Location:documents.php");
    
} 
else {
  echo "Error: " . $sql . "<br>" . mysqli_error($conn);
}
mysqli_close($conn);

?>