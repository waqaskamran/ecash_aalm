<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
	xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
	xmlns:xsd="http://www.w3.org/2001/XMLSchema"
	xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding">

	<xsl:output method="xml" />

	<xsl:template match="/service">
		<wsdl:definitions>
			<xsl:variable name="myparam">urn:<xsl:value-of select="@name" /></xsl:variable>
			<xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>
			<xsl:attribute name="targetNamespace">urn:<xsl:value-of select="@name" /></xsl:attribute>
		<!--	<xsl:attribute name="xmlns:tns">urn:<xsl:value-of select="@name" /></xsl:attribute>-->
			<xsl:attribute name="tns:dummy" namespace="{$myparam}"/>

			<wsdl:types />

			<wsdl:portType>
				<xsl:attribute name="name"><xsl:value-of select="@name" />Port</xsl:attribute>
				<xsl:apply-templates mode="port"/>
			</wsdl:portType>

			<wsdl:binding>
				<xsl:attribute name="name"><xsl:value-of select="@name" />Binding</xsl:attribute>
				<xsl:attribute name="type">tns:<xsl:value-of select="@name" />Port</xsl:attribute>
				<soap:binding>
					<xsl:attribute name="style">rpc</xsl:attribute>
					<xsl:attribute name="transport">http://schemas.xmlsoap.org/soap/http</xsl:attribute>
				</soap:binding>
				<xsl:apply-templates mode="binding" />
			</wsdl:binding>

			<wsdl:service>
				<xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>
				<wsdl:documentation />
				<wsdl:port>
					<xsl:attribute name="name"><xsl:value-of select="@name" />Port</xsl:attribute>
					<xsl:attribute name="binding">tns:<xsl:value-of select="@name" />Binding</xsl:attribute>
					<soap:address>
						<xsl:attribute name="location"><xsl:value-of select="@url" /></xsl:attribute>
					</soap:address>
				</wsdl:port>
			</wsdl:service>

			<xsl:apply-templates mode="message" />

		</wsdl:definitions>

	</xsl:template>

	<xsl:template match="method" mode="port">
		<wsdl:operation>
			<xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>
			<xsl:apply-templates mode="port"/>
		</wsdl:operation>
	</xsl:template>

	<xsl:template match="arguments" mode="port">
		<wsdl:input>
			<xsl:attribute name="message">tns:<xsl:value-of select="../@name" />Request</xsl:attribute>
		</wsdl:input>
	</xsl:template>

	<xsl:template match="response" mode="port">
		<wsdl:output>
			<xsl:attribute name="message">tns:<xsl:value-of select="../@name" />Response</xsl:attribute>
		</wsdl:output>
	</xsl:template>

	<xsl:template match="method" mode="binding">
		<wsdl:operation>
			<xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>
			<soap:operation>
				<xsl:attribute name="soapAction">urn:<xsl:value-of select="../@name" />#<xsl:value-of select="@name" /></xsl:attribute>
			</soap:operation>
			<xsl:apply-templates mode="binding"/>
		</wsdl:operation>
	</xsl:template>

	<xsl:template match="arguments" mode="binding">
		<wsdl:input>
			<soap:body>
				<xsl:attribute name="namespace">urn:<xsl:value-of select="../../@name" /></xsl:attribute>
				<xsl:attribute name="use">encoded</xsl:attribute>
				<xsl:attribute name="encodingStyle">http://schemas.xmlsoap.org/soap/encoding/</xsl:attribute>
			</soap:body>
		</wsdl:input>
	</xsl:template>

	<xsl:template match="response" mode="binding">
		<wsdl:output>
			<soap:body>
				<xsl:attribute name="namespace">urn:<xsl:value-of select="../../@name" /></xsl:attribute>
				<xsl:attribute name="use">encoded</xsl:attribute>
				<xsl:attribute name="encodingStyle">http://schemas.xmlsoap.org/soap/encoding/</xsl:attribute>
			</soap:body>
		</wsdl:output>
	</xsl:template>

	<xsl:template match="method" mode="message">
		<xsl:if test="count(arguments/argument) &gt; 0">
			<wsdl:message>
				<xsl:attribute name="name"><xsl:value-of select="@name" />Request</xsl:attribute>
				<xsl:apply-templates mode="message" />
			</wsdl:message>
		</xsl:if>
		<xsl:if test="count(response) &gt; 0">
			<wsdl:message>
				<xsl:attribute name="name"><xsl:value-of select="@name" />Response</xsl:attribute>
				<wsdl:part>
					<xsl:attribute name="type">xsd:<xsl:value-of select="response/@type" /></xsl:attribute>
					<xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>
				</wsdl:part>
			</wsdl:message>
		</xsl:if>
	</xsl:template>

	<xsl:template match="arguments" mode="message">
		<xsl:apply-templates mode="message" />
	</xsl:template>

	<xsl:template match="argument" mode="message">
		<wsdl:part>
			<xsl:attribute name="type">xsd:<xsl:value-of select="@type" /></xsl:attribute>
			<xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>
		</wsdl:part>
	</xsl:template>

</xsl:stylesheet>