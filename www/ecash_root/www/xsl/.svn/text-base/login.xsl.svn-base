<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet [<!ENTITY nbsp "&#160;">]>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="/">
	<xsl:variable name="bg">
		<xsl:choose>
			<xsl:when test="/@ecash_mode = 'LIVE'">bg_live</xsl:when>
			<xsl:when test="/@ecash_mode = 'RC'">bg_rc</xsl:when>
			<xsl:otherwise>bg_local</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<html>
		<head>
			<title>eCash <xsl:value-of select="/ecash/@major_version" />.<xsl:value-of select="/ecash/@minor_version" /> Build <xsl:value-of select="/ecash/@build_version" /> - <xsl:value-of select="/ecash/@database" /></title>
			<link rel="stylesheet" href="css/style.css" />
			<script type="text/javascript" src="js/login.js"></script>
		</head>
		<body>
			<xsl:attribute name="class"><xsl:value-of select="$bg" /></xsl:attribute>
			<center>
				<form>
					<xsl:attribute name="action">/</xsl:attribute>
					<xsl:attribute name="method">post</xsl:attribute>
					<xsl:attribute name="id">login_form</xsl:attribute>
					<xsl:attribute name="onSubmit">return Set_Company_Host_Location();</xsl:attribute>
					<input>
						<xsl:attribute name="type">hidden</xsl:attribute>
						<xsl:attribute name="name">page</xsl:attribute>
						<xsl:attribute name="value">login</xsl:attribute>						
					</input>
					<table cellpadding="0" cellspacing="0">
						<xsl:apply-templates select="//error" />
						<tr>
							<td class="border">
								<table cellpadding="0" cellspacing="0">
									<tr>
										<xsl:attribute name="class"><xsl:value-of select="$bg" /></xsl:attribute>
										<th colspan="2">
											eCash Login - <xsl:value-of select="/ecash/@execution_mode" />
										</th>
									</tr>
									<tr>
										<xsl:attribute name="class"><xsl:value-of select="$bg" /></xsl:attribute>
										<td class="align_left_bold"><xsl:text>&nbsp; Username: &nbsp;</xsl:text></td>
										<td class="align_left">
											<input type="text" name="login">
												<xsl:attribute name="value"><xsl:value-of select="//agent/login" /></xsl:attribute>
											</input>
										</td>
									</tr>
									<tr>
										<xsl:attribute name="class"><xsl:value-of select="$bg" /></xsl:attribute>
										<td class="align_left_bold"><xsl:text>&nbsp; Password: &nbsp;</xsl:text></td>
										<td class="align_left">
											<input type="password" name="password" />
										</td>
									</tr>
									<xsl:if test="//pbx[@enabled = 'true']">
										<tr>
											<xsl:attribute name="class"><xsl:value-of select="$bg" /></xsl:attribute>
											<td class="align_left_bold"><xsl:text>&nbsp; Extension: &nbsp;</xsl:text></td>
											<td class="align_left">
												<input type="text" name="phone_extension">
													<xsl:attribute name="value"><xsl:value-of select="//agent/phone_extension" /></xsl:attribute>
												</input>
											</td>
										</tr>
									</xsl:if>
									<tr>
									</tr>
									<xsl:choose>
										<xsl:when test="count(//company) &gt; 1" >
											<tr>
												<xsl:attribute name="class"><xsl:value-of select="$bg" /></xsl:attribute>
												<td class="align_left_bold"><xsl:text>&nbsp; Company: &nbsp;</xsl:text></td>
												<td class="align_left">
													<xsl:apply-templates select="//companies" mode="option_list" />
												</td>
											</tr>
										</xsl:when>
										<xsl:when test="count(//company) = 1" >
											<input>
												<xsl:attribute name="type">hidden</xsl:attribute>
												<xsl:attribute name="name">abbrev</xsl:attribute>
												<xsl:attribute name="id">login_company</xsl:attribute>
												<xsl:attribute name="value"><xsl:value-of select="//companies/company/@name_short" /></xsl:attribute>
											</input>
										</xsl:when>
									</xsl:choose>
									<tr>
										<xsl:attribute name="class"><xsl:value-of select="$bg" /></xsl:attribute>
										<td colspan="2">
											<input>
												<xsl:attribute name="type">submit</xsl:attribute>
												<xsl:attribute name="value">login</xsl:attribute>
												<xsl:attribute name="class">button</xsl:attribute>
											</input>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</form>
			</center>
		</body>
	</html>
</xsl:template>

<xsl:template match="companies" mode="option_list">
	<select>
		<xsl:attribute name="name">abbrev</xsl:attribute>
		<xsl:attribute name="id">login_company</xsl:attribute>
		<xsl:apply-templates mode="option_list"/>
	</select>
</xsl:template>

<xsl:template match="company" mode="option_list">
	<option>
		<xsl:attribute name="value"><xsl:value-of select="@name_short" /></xsl:attribute>
		<xsl:if test="@name_short = ../@default">
			<xsl:attribute name="selected" value="selected" />
		</xsl:if>
		<xsl:value-of select="." /> 
	</option>
</xsl:template>

<xsl:template match="error">
	<tr>
		<th colspan="2" style="background: red;"><xsl:value-of select="." /></th>
	</tr>
</xsl:template>

</xsl:stylesheet>