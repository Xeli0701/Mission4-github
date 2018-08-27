<?php
    /*パスワードが平文だったりと酷い内容だけどとりあえずは動く
        課題
            stmtとSQLの動かし方（->)などがどのような動作なのか正直あまりよく分かっていない。
            削除・編集機能は存在しない投稿番号を押しても「正常完了」と表示されてしまう
    */

    //DB接続
    $dsn = 'hoge'; //データベース名
    $user = 'hoge'; //ユーザ名
    $password = 'hoge'; //パスワード
    $pdo = new PDO($dsn,$user,$password);

    //入力データを取得
	$name = $_POST['name']; //入力した名前
	$comment = $_POST['comment']; //入力したコメント
	$password_set = $_POST['password_set']; //設定したパスワード
	$id_delete = $_POST['id_delete']; //削除する投稿番号
	$id_edit = $_POST['id_edit_post']; //編集する投稿番号
	$id_edit_update = $_POST['id_edit_update']; //編集する投稿番号(id_edit_postと同一)
	$password = $_POST['password']; //編集・削除時のパスワード

    if($_POST['submit_send']){ //送信ボタンが押された時

        //投稿番号取得
        $sql_count = "SELECT id FROM mission4_bbs ORDER BY id DESC LIMIT 1"; //最新の投稿(一番大きいid)を表示
        $stmt = $pdo -> query($sql_count);
        $id = ($stmt->fetchColumn() + 1); //投稿番号(ID)

        //書き込み機能分類分け
        if(ctype_digit($id_edit_update) and strlen($name) and strlen($comment) ){ //編集機能用フォームに記入済みの場合
            $sql = "UPDATE mission4_bbs SET name='$name', comment='$comment' WHERE id = $id_edit_update";
            $result = $pdo -> query($sql);
            echo "正常完了：投稿番号[" .$id_edit_update. "]を編集しました" . '<br>';

        } else if(strlen($name) and strlen($comment) and strlen($password_set)){  //通常書き込み
            //SQLの書き込みの準備
            $sql = $pdo -> prepare("INSERT INTO mission4_bbs(id, name, comment, posttime, pass) VALUES(:id, :name, :comment, now(), :pass)");
            //各種パラメータを入力
            $sql -> bindParam(':id', $id, PDO::PARAM_INT);
            $sql -> bindParam(':name', $name, PDO::PARAM_STR);
            $sql -> bindParam(':comment', $comment, PDO::PARAM_STR);
            $sql -> bindParam(':pass', $password_set, PDO::PARAM_STR);
            //実行
            $sql -> execute();
            echo "正常完了：投稿されました" . '<br>';

        } else { //未入力パラメータがある場合のエラーメッセージm
            echo "エラー：名前・コメント・パスワードを入力しなければ投稿出来ません。" . "<br>";
        }
    }

    if($_POST['submit_delete']){ //削除ボタンが押された時
        //idが$id_delete(削除対象番号)かつパスワードが一致したものを削除
        $sql = "SELECT * FROM mission4_bbs WHERE id=$id_delete" ;
        $stmt = $pdo -> query($sql);
        if(strcmp($stmt->fetchColumn(4) , $password) == 0){
            $sql = "DELETE FROM mission4_bbs WHERE id=$id_delete";
            $pdo -> query($sql);
            echo "正常完了：投稿番号[" .$id_delete. "]は削除されました" . '<br>';
        } else {
            echo "エラー：パスワードが一致しません" . '<br>';
        }
    }

    if($_POST['submit_edit']){ //編集ボタンが押された時
        //DBを検索
        $sql = "SELECT * FROM mission4_bbs WHERE id=$id_edit";
        $stmt = $pdo -> query($sql);
        if(strcmp($stmt->fetchColumn(4) , $password) == 0){ //パスワードが一致する場合
            $sql = "SELECT * FROM mission4_bbs WHERE id=$id_edit"; //$stmtを再取得？ ->でやってるから変化するんだよね　よくここらへんの仕様が分からないのでとりあえずやった！動く！って感じに
            $stmt = $pdo -> query($sql);
            foreach($stmt as $row){
                $eName = $row['name'];
                $eComment = $row['comment'];
                $eID = $id_edit;
            }
            echo "正常完了：編集後、送信ボタンを押してください" . '<br>';
        } else {
            echo "エラー：パスワードが一致しません" . '<br>';
        }
    }

?>

<!DOCTYPE html>
<html lang="ja">
<head> <meta charset="utf-8"></head>
<body>
	<form action="" method="post">
	<br>
	書き込みフォーム
	<hr>

        名前　　　：
		<input type="text" name="name" placeholder="名前" value="<?php echo $eName; ?>">
        <br>
        コメント　：
        <input type="text" name="comment" placeholder="コメント" value="<?php echo $eComment; ?>">
		<br>
		パスワード：
		<input type="text" name="password_set" placeholder="パスワード">
		<input type="submit" name="submit_send" value="送信">
		<!-- 編集用フォーム -->
		<input type="text" name="id_edit_update" value="<?php echo $eID; ?>">

	</form>
	<br>
	編集・削除フォーム
	<hr>

	<form action="" method="post">
        削除対象番号：
		<input type="text" name="id_delete">
		パスワード：
		<input type="text" name="password" placeholder="パスワード">
		<input type="submit" name="submit_delete" value="削除">
	</form>

	<form action="" method="post">
		編集対象番号：
		<input type="text" name="id_edit_post">
		パスワード：
		<input type="text" name="password" placeholder="パスワード">
		<input type="submit" name="submit_edit" value="編集">
	</form>

    ※名前は32文字以内、パスワードは50文字以内で入力してください。それ以上入力した場合省略されます
    ※パスワードは編集出来ません

	<!-- 本来であればラベル化した方が良いが…… -->

	<br>
	掲示板
	<hr>
    <?php
    //掲示板の表示
    $sql = 'SELECT * FROM mission4_bbs ORDER BY id ASC';
    $result = $pdo -> query($sql);

    foreach ($result as $row){
    //$rowの中にはカラムが入る
        echo "ID[".$row['id'] . '] ';
        echo "Name[" . $row['name'] . '] ';
        echo "Time[" . $row['posttime'] . '] ';
        echo "PASS(Debug)[" . $row['pass'] . ']<br>';
        echo 'Comment：' . $row['comment'] .  '<br><br>';

    }
    ?>
