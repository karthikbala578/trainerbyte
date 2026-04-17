<?php
require "../../include/dataconnect.php";

$bn_id = intval($_POST['bn_id']);
$status = intval($_POST['bn_status']);

$pill = mysqli_real_escape_string($conn, $_POST['bn_pill_text']);
$title = mysqli_real_escape_string($conn, $_POST['bn_title']);
$desc = mysqli_real_escape_string($conn, $_POST['bn_short_desc']);

$image_name = "";

if (!empty($_FILES['bn_image']['name'])) {

    $image_name = time() . "_" . $_FILES['bn_image']['name'];

    move_uploaded_file(
        $_FILES['bn_image']['tmp_name'],
        "banner-uploads/" . $image_name
    );
}

if ($bn_id > 0) {

    if ($image_name != "") {

        $query = "
            UPDATE tb_cms_banner SET
            bn_pill_text='$pill',
            bn_title='$title',
            bn_short_desc='$desc',
            bn_image='$image_name',
            bn_status=$status,
            bn_updated_at=NOW()
            WHERE bn_id=$bn_id
        ";
    
    } else {
    
        $query = "
            UPDATE tb_cms_banner SET
            bn_pill_text='$pill',
            bn_title='$title',
            bn_short_desc='$desc',
            bn_status=$status,
            bn_updated_at=NOW()
            WHERE bn_id=$bn_id
        ";
    
    }
    
} else {

    $query = "
        INSERT INTO tb_cms_banner
        (bn_pill_text, bn_title, bn_short_desc, bn_image, bn_status, bn_created_at)
        VALUES
        ('$pill', '$title', '$desc', '$image_name', 1, NOW())
    ";
}

$result = mysqli_query($conn, $query);

if ($result) {

    if ($bn_id > 0) {
        echo $bn_id;   // return existing id
    } else {
        echo mysqli_insert_id($conn);  // return new inserted id
    }

} else {
    echo "error";
}

exit;

