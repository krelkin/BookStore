<?php

	include "connectToDataBase.php";
	
	if( isset($_GET["page"]) )
		$pageNumber = $_GET["page"] - 1;
	else 
		$pageNumber = 0;
	
    $quantityElementsOfMainSlider = 2;
    $quantityElementsOfSecondarySlider = 5;
    $quantityElementsOfMiddleContent = htmlspecialchars($_COOKIE["quantityElem"]);
	
	if ( !isset($id) )
		if ( isset($_POST['id']) ) $id = $_POST['id'];
		else if ( isset($_GET['id']) ) $id = $_GET['id'];
	
    switch($id){
        case 0: $quantityOfElements = $quantityElementsOfMainSlider;
            break;
        case 1: $quantityOfElements = $quantityElementsOfSecondarySlider;
            break;
        case 2: $quantityOfElements = $quantityElementsOfMiddleContent;
            break;
    };
	
    $rand_arr = [];
	
	if ($id != 2)
		$query = "SELECT book_id FROM books";
	else{
		$arr = [];
		if( $res = $sqlBase->query( "SELECT book_id FROM books limit " . ($quantityOfElements * $pageNumber) ) )
			while($row = $res->fetch_assoc() )
				$arr[] = $row["book_id"];
		if ( count($arr) > 0 )
			$c = implode(", ", $arr);
		else 
			$c = "0";
		$query = "SELECT book_id FROM books WHERE book_id NOT IN (" . $c . ") limit " . $quantityOfElements;
		unset($arr);
	};

	if ($result = $sqlBase->query( $query ) )
        while ($row = $result->fetch_assoc() )
            $rand_arr[] = $row['book_id'];
		
	$allBooks = 0;
	if ($result = $sqlBase->query( "SELECT count(book_id) AS allBooks FROM books" ) )
		while( $row = $result->fetch_assoc() )
			$allBooks = $row["allBooks"];
		
	if($id != 4 && $id != 5)
		while( count($rand_arr) > $quantityOfElements )
			unset ( $rand_arr[ rand(0, count($rand_arr) - 1) ] );
	
	$query = "SELECT book_id, title, price, description FROM books";
	
	if($id != 4 && $id != 5){
		$i = true;
		foreach($rand_arr as $key => $value){
			if ($i)
				$query .= " WHERE book_id = " . $value;
			else
				$query .= " OR book_id = " . $value;

			$i = false;
		}
	}
	if ($id == 5){
		if( !isset($book_id_select) ){
			$book_id_select = 0;
			if( isset($_GET["book_id_select"]) ) $book_id_select = $_GET["book_id_select"];
			if( isset($_POST["book_id_select"])) $book_id_select = $_POST["book_id_select"];
		}
		
		if($book_id_select > 0)
			$query .= " WHERE book_id = " . $book_id_select;
	}
	
    $query .= " ORDER BY book_id";
	
	$res_arr = [];
	if ($result = $sqlBase->query( $query ))
        while ($row = $result->fetch_assoc() ){
            switch($id){
                case 0:
                    echo
                    "<li>
                        <div class='overlay'></div>
                            <div class='container'>
                                <div class='row'>
                                    <div class='col-md-5 col-lg-4'>
                                        <div class='flex-caption visible-lg'>
                                        <span class='price'>$" . $row['price'] . "</span>
                                        <h3 class='title'>" . $row['title'] . "</h3>
                                            <p>" . $row['description'] . "</p>
                                        <a href='book.php?book_id=" . $row['book_id'] . "' class='slider-btn'>  BUY NOW </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>";
                    break;
                case 1:
                    $book_id = $row['book_id'];
                    include "getGenreList.php";
                    include "getAuthorList.php";
                    echo
                    '<div class="list-item">
                        <div class="list-thumb">
                            <div class="title">
                                <h4><a style = "color:white" href = "book.php?book_id=' . $book_id . '">' . $row['title'] . '</a></h4>
                            </div>
                            <img src="images/destination_1.jpg" alt="">
                        </div>
                        <div class="list-content">
                            <h5>' . $authors . '</h5>
                            <span>' . $genres . '</span>
                            <a href="book.php?book_id=' . $row['book_id'] . '" class="price-btn">$' . $row['price'] . '</a>
                        </div>
                    </div>';
                    break;
                case 2:
                    $book_id = $row['book_id'];
					$get_array = true;
					include "getGenreList.php";
                    include "getAuthorList.php";
					
					$str = '';
					$str .= 
					'<div class = "panel panel-warning">
						<div class="row list-item panel-heading"  style="margin:0px">
							<div class="col-xs-12 list-thumb">
								<span class="col-xs-12 title"><h4><a style = "color:white" href = "book.php?book_id=' . $book_id . '">' . $row['title'] . '</a></h4></span>
							</div>
							<div class="col-xs-12">
								<dl><dt>Авторы </dt><dd class="authors_list">';
									foreach($authors_arr as $key => $value){
										$str .= '<a href=searchpage.php?author_id=' . $value["author_id"] . ' class = "btn btn-success">' . $value["author_name"] . '</a>';
									}
					$str .= '</dd><dt>Жанры </dt><dd class="genre_list">';
									foreach($genres_arr as $key => $value){
										$str .= '<a href=searchpage.php?genre_id=' . $value["genre_id"] . ' class = "btn btn-warning">' . $value["genre_name"] . '</a>';
									}
					$str .=	'</dd><dt>Описание</dt><dd>'
										. substr($row['description'], 0, 400)
									.'...</dd></dl>
								<a class = "col-xs-12 btn btn-info" href = "book.php?book_id=' . $book_id . '">' . $row['price'] . ' грн.</a>
							</div>
						</div>
					</div>';
					echo $str;
					break;
				case 4:
					$book_id = $row['book_id'];
					$get_array = true;
                    include "getAuthorList.php";
					$res_arr[] = [  "book_id" 		=> $row['book_id'], 
									"title" 		=> $row['title'] , 
									"authors" 		=> $authors_arr ];
					break;
				case 5:
					$book_id = $row['book_id'];
					$get_array = true;
					include "getGenreList.php";
                    include "getAuthorList.php";
					$res_arr[] = [  "book_id" 		=> $row['book_id'], 
									"title" 		=> $row['title'] , 
									"description"	=> substr($row['description'], 0, 400),
									"price"			=> $row['price'],
									"authors" 		=> $authors_arr,
									"genres"		=> $genres_arr ];
					break;
            }
		}
		
		$str = "";
		if($id == 2){
			$str .= '<div class="btn-group" role="group">';
				for($i = 1; $i <= ceil($allBooks / $quantityOfElements); $i++){
					$active = "";
					if ($i == $pageNumber + 1) $active = "active";
					$str .= '<button type="button" class="btn btn-default ' . $active . '" name="pageBtn' . $i . '" id="btn' . $i . '" onclick="choosePage(' . $i . ')">' . $i . '</button>';
				};
			$str .= '</div>';
			echo $str;
		}

	if ($id == 4 || $id == 5) echo json_encode($res_arr);
?>