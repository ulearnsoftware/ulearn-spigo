<html>
<head>
<title>{PROJECT_NAME} - P&aacute;gina de Login</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" href="skins/{SKIN}/_common/styles.css">
</head>

<body class="login" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<form method="POST">
<p>&nbsp;</p>
<table width="400" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr>
	<td>
		<TABLE cellpadding="0" cellspacing="0" align='center' width='100%'>
			<TR>
				<TD class='menutableleftcorneroff' width=6><IMG SRC="{IMG_PATH}/1x1.gif" width=6 height=6></TD>
				<TD class='menutabletopborderoff'><IMG SRC="{IMG_PATH}/1x1.gif" width=6 height=6></TD>
				<TD class="menutablerightcorneroff" width=6><IMG SRC="{IMG_PATH}/1x1.gif" width=6 height=6></TD>
			</TR>
			<TR>
				<TD class="menutableleftborderoff"><IMG SRC="{IMG_PATH}/1x1.gif" width=6 height="100%"></TD>
				<TD class="menutabletaboff"><img src="{RUTA_IMG}/logo_login.gif"></TD>
				<TD class="menutablerightborderoff"><IMG SRC="{IMG_PATH}/1x1.gif" width=6 height=6></TD>
			</TR>
		</TABLE>
	</td>
	</tr>
	<tr>
	 <td>
	    <table cellspacing="1" cellpadding="4" bgcolor="#999999" align="center" valign="top">
           <tr>
		      <td class="table_header">
			     <div align='center'><big>&nbsp;&raquo; <b>Ventana de ingreso</b></big></div>
			  </td>
		    </tr>
    		<tr>
        	  <td width="498">
                  <table width="100%" border="0" cellspacing="0" cellpadding="6">
						<tr>
							<td colspan="2" align="center" class="error">{ERROR_BOX}</td>
						</tr>
						<tr>
							<th align="right">Usuario:</th>
							<td><input
								class="border"
								type="text"
								name="input_user"></td>
						</tr>
						<tr>
							<th align="right">Contrase&ntilde;a:</th>
							<td><input
								class="border"
								type="password"
								name="input_pass"></td>
						</tr>
						<tr>
							<td colspan="2" align="center"><input
								type="submit"
								name="submit_login"
								value="Submit"
								class="submit"></td>
						</tr>
						
					</table>
               </td>
            </tr>
		  </table>
      </td>
</tr>
</table>
</form>
</body>
</html>
