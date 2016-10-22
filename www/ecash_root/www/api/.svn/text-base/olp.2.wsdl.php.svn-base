<?php
	header('Content-Type: text/xml');
	echo '<?xml version="1.0"?>';
?>
<wsdl:definitions xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
	xmlns:tns="http://schemas.sellingsource.com/soap/ecash/customer_authentication"
	xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
	name="OlpApi"
	targetNamespace="http://schemas.sellingsource.com/soap/ecash/customer_authentication">
	<wsdl:types>
		<xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema"
			targetNamespace="http://schemas.sellingsource.com/soap/ecash/customer_authentication">
			<xsd:element name="NewElement" type="xsd:string"></xsd:element>
		</xsd:schema>
	</wsdl:types>
	<wsdl:message name="Validate">
        <wsdl:part name="application_id" type="xsd:integer"></wsdl:part>
        <wsdl:part name="phone_work" type="xsd:string"></wsdl:part>
        <wsdl:part name="dob" type="xsd:string"></wsdl:part>
        <wsdl:part name="is_react" type="xsd:boolean"></wsdl:part>
	</wsdl:message>
	<wsdl:message name="ValidateResponse">
		<wsdl:part name="ValidateResponse" type="xsd:string" />
	</wsdl:message>
	<wsdl:message name="isLocked">
		<wsdl:part name="application_id" type="xsd:int"></wsdl:part>
		<wsdl:part name="is_react" type="xsd:boolean"></wsdl:part>
	</wsdl:message>
	<wsdl:message name="isLockedResponse">
		<wsdl:part name="isLockedResponse" type="xsd:boolean"></wsdl:part>
	</wsdl:message>
	<wsdl:message name="getDataByApplicationId">
		<wsdl:part name="application_id" type="xsd:int"></wsdl:part>
	</wsdl:message>
	<wsdl:message name="getDataByApplicationIdResponse">
		<wsdl:part name="customer_information" type="apache:Map"></wsdl:part>
	</wsdl:message>
	<wsdl:message name="isApplicationReactable">
		<wsdl:part name="application_id" type="xsd:int"></wsdl:part>
	</wsdl:message>
	<wsdl:message name="isApplicationReactableResponse">
		<wsdl:part name="customer_information" type="xsd:boolean"></wsdl:part>
	</wsdl:message>
	<wsdl:portType name="OlpApiPortType">
		<wsdl:operation name="Validate">
			<wsdl:input message="tns:Validate" />
			<wsdl:output message="tns:ValidateResponse" />
		</wsdl:operation>
		<wsdl:operation name="isLocked">
			<wsdl:input message="tns:isLocked"></wsdl:input>
			<wsdl:output message="tns:isLockedResponse"></wsdl:output>
		</wsdl:operation>
		<wsdl:operation name="getDataByApplicationId">
			<wsdl:input message="tns:getDataByApplicationId"></wsdl:input>
			<wsdl:output message="tns:getDataByApplicationIdResponse"></wsdl:output>
		</wsdl:operation>
		<wsdl:operation name="isApplicationReactable">
			<wsdl:input message="tns:isApplicationReactable"></wsdl:input>
			<wsdl:output message="tns:isApplicationReactableResponse"></wsdl:output>
		</wsdl:operation>
	</wsdl:portType>
	<wsdl:binding name="OlpApiBinding" type="tns:OlpApiPortType">
		<soap:binding style="rpc"
			transport="http://schemas.xmlsoap.org/soap/http" />
		<wsdl:operation name="Validate">
			<soap:operation
				soapAction="http://schemas.sellingsource.com/soap/ecash/customer_authentication/Validate" />
			<wsdl:input>
				<soap:body
					namespace="http://schemas.sellingsource.com/soap/ecash/customer_authentication"
					use="literal" />
			</wsdl:input>
			<wsdl:output>
				<soap:body
					namespace="http://schemas.sellingsource.com/soap/ecash/customer_authentication"
					use="literal" />
			</wsdl:output>
		</wsdl:operation>
		<wsdl:operation name="isLocked">
			<soap:operation
				soapAction="http://schemas.sellingsource.com/soap/ecash/customer_authentication/isLocked" />
			<wsdl:input>
				<soap:body use="literal"
					namespace="http://schemas.sellingsource.com/soap/ecash/customer_authentication" />
			</wsdl:input>
			<wsdl:output>
				<soap:body use="literal"
					namespace="http://schemas.sellingsource.com/soap/ecash/customer_authentication" />
			</wsdl:output>
		</wsdl:operation>
		<wsdl:operation name="getDataByApplicationId">
			<soap:operation
				soapAction="http://schemas.sellingsource.com/soap/ecash/customer_authentication/getDataByApplicationId" />
			<wsdl:input>
				<soap:body use="literal"
					namespace="http://schemas.sellingsource.com/soap/ecash/customer_authentication" />
			</wsdl:input>
			<wsdl:output>
				<soap:body use="literal"
					namespace="http://schemas.sellingsource.com/soap/ecash/customer_authentication" />
			</wsdl:output>
		</wsdl:operation>
		<wsdl:operation name="isApplicationReactable">
			<soap:operation
				soapAction="http://schemas.sellingsource.com/soap/ecash/customer_authentication/isApplicationReactable" />
			<wsdl:input>
				<soap:body use="literal"
					namespace="http://schemas.sellingsource.com/soap/ecash/customer_authentication" />
			</wsdl:input>
			<wsdl:output>
				<soap:body use="literal"
					namespace="http://schemas.sellingsource.com/soap/ecash/customer_authentication" />
			</wsdl:output>
		</wsdl:operation>
	</wsdl:binding>
	<wsdl:service name="OlpApi">
		<wsdl:port binding="tns:OlpApiBinding" name="OlpApiPort">
			<soap:address location="<?php echo $soap_url ?>" />
		</wsdl:port>
	</wsdl:service>
</wsdl:definitions>

