<?php
	global $conn;

	include_once('includes/session.php');
	include_once('includes/functions_db.php');
	include_once('includes/functions_poll.php');

	$conn = null;
	connectDB();

	if(login_check())
	{
		if (isset($_POST['poll']) && isset($_POST['poll_selection']))
		{
			if (is_numeric($_POST['poll']) && is_array($_POST['poll_selection']))
			{
				//Check whether this uid has voted already
				$stmt =  $conn->prepare("select count(*) as voted from poll_respondent where uid = :uid");
				$stmt->execute(array( 'uid' => $uid ));
				if ($stmt)
				{
					$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
					$stmt->closeCursor();
					if ($result[0]['voted'] == 0)
					{

						$stmt =  $conn->prepare("select multipleChoice, now() between publishDate and expireDate as canVote from poll where id = :pollid");
						$stmt->execute(array( 'pollid' => $_POST['poll']));
						$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
						$stmt->closeCursor();
						if ($result[0]['canVote'] == 1)
						{
							//Check whether this is a multiple choice poll and bail if there are too many responses
							if ((count($_POST['poll_selection']) > 1 && $result[0]['multipleChoice'] > 0) || (count($_POST['poll_selection']) == 1))
							{
						
								$stmt = $conn->prepare("insert into poll_respondent (uid, pollID) values (:steamid, :pollid)");
								$stmt->execute(array( 'steamid' => $_SESSION['u'], 'pollid' => $_POST['poll']));
								$stmt->closeCursor();

								$stmt = $conn->prepare("update poll_option set responseCount = responseCount + 1 where id = :optionid");

								foreach ($_POST['poll_selection'] as $o)
								{
									if (is_numeric($o))
									{
										$stmt->execute(array( 'optionid' => $o));
										$stmt->closeCursor();
									}
								}
							}
							else
							{
								$error = "too_many_choices";
							}
						}
						else
						{
							$error = "poll_not_open";
						}


					}
					else
					{
						$error = "already_voted_" . $result[0]['voted'];
					}
				}
				else
				{
					$error = "system_error";
				}

			}
			else
			{
				$error = "bad_selection";
			}
		}
		else
		{
			$error = "bad_poll";
		}
	}
	else
	{
		$error = "logged_out";
	}

	if (!isset($_POST['page']))
	{
		$_POST['page'] = "/";
	}
	if (isset($error))
	{
		header("location: " . $_POST['page'] . "?error=" . $error);
	}
	else
	{
		header("location: " . $_POST['page']);
	}


?>
