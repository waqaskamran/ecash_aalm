<?php 
header("Content-type: text/xml", TRUE);
echo "<?xml version=\"1.0\" encoding='UTF-8'?>"; ?>
<definitions 
		xmlns="http://schemas.xmlsoap.org/wsdl/" 
		xmlns:tns="http://sellingsource.com/nada" 
		xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/" 
		xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
		xmlns:soap-enc="http://schemas.xmlsoap.org/soap/encoding/" 
		xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/" 
		name="NADA_SOAP" 
		targetNamespace="http://sellingsource.com/nada">
	<types>
		<xsd:complexType name="vehicle">
			<xsd:sequence>
				<xsd:element name="make" type="xsd:string"/>
				<xsd:element name="model" type="xsd:string"/>
				<xsd:element name="series" type="xsd:string"/>
				<xsd:element name="body" type="xsd:string"/>
				<xsd:element name="vicYear" type="xsd:string"/>
				<xsd:element name="vin" type="xsd:string"/>
				<xsd:element name="value" type="xsd:string"/>
			</xsd:sequence>
		</xsd:complexType>
	</types>	
	<portType name="NADA_SOAPPort">
		<operation name="getVehicleByVin">
			<documentation>Gets a Vehicle's information based on the 17 digit VIN# (actually 9 of the first 10 digits of it)</documentation>
			<input message="tns:getVehicleByVinIn"/>
			<output message="tns:getVehicleByVinOut"/>
		</operation>
		<operation name="getValueFromDescription">
			<documentation>Gets the value for a vehicle from description values</documentation>
			<input message="tns:getValueFromDescriptionIn"/>
			<output message="tns:getValueFromDescriptionOut"/>
		</operation>
	</portType>
	<binding name="NADA_SOAPBinding" type="tns:NADA_SOAPPort">
		<soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http"/>
		<operation name="getVehicleByVin">
			<soap:operation soapAction="http://sellingsource.com/nada#getVehicleByVin"/>
			<input>
				<soap:body use="literal" namespace="http://sellingsource.com/nada"/>
			</input>
			<output>
				<soap:body use="literal" namespace="http://sellingsource.com/nada"/>
			</output>
		</operation>
		<operation name="getValueFromDescription">
			<soap:operation soapAction="http://sellingsource.com/nada#getValueFromDescription"/>
			<input>
				<soap:body use="literal" namespace="http://sellingsource.com/nada"/>
			</input>
			<output>
				<soap:body use="literal" namespace="http://sellingsource.com/nada"/>
			</output>
		</operation>
	</binding>
	<service name="NADA_SOAPService">
		<port name="NADA_SOAPPort" binding="tns:NADA_SOAPBinding">
			<soap:address location="<?php echo htmlentities($soap_path); ?>"/>
		</port>
	</service>
	<message name="getVehicleByVinIn">
		<part name="vin" type="xsd:string"/>
		<part name="regionid" type="xsd:string"/>
		<part name="valuetype" type="xsd:string"/>
		<part name="state_code" type="xsd:string"/>
	</message>
	<message name="getVehicleByVinOut">
		<part name="return" type="tns:vehicle"/>
	</message>
	<message name="getValueFromDescriptionIn">
		<part name="make" type="xsd:string"/>
		<part name="model" type="xsd:string"/>
		<part name="series" type="xsd:string"/>
		<part name="body" type="xsd:string"/>
		<part name="year" type="xsd:string"/>
	</message>
	<message name="getValueFromDescriptionOut">
		<part name="return" type="xsd:string"/>
	</message>	
</definitions>
