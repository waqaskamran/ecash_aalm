<?php

function Main()
{
	//instance token manager
	$manager = ECash::getFactory()->getTokenManager();

	//creation of a universal static token
	$new = $manager->getNewToken('static');
	$new->setName('test');
	$new->setValue('foshizzle1');
	$new->save();
	//creation of a company static token
	$new2 = $manager->getNewToken('static');
	$new2->setName('test');
	$new2->setValue('foshizzle2');
	$new2->setCompanyId(1);
	$new2->save();
	//creation of a loan type static token
	$new3 = $manager->getNewToken('static');
	$new3->setName('test');
	$new3->setValue('foshizzle3');
	$new3->setCompanyId(1);
	$new3->setLoanTypeId(22);
	$new3->save();
	//creation of a loan type application token
	$new4 = $manager->getNewToken('application');
	$new4->setName('loan first name');
	$new4->setValue('name_first');
	$new4->setCompanyId(1);
	$new4->setLoanTypeId(22);
	$new4->save();
	//creation of a loan type business rule token
	$new5 = $manager->getNewToken('business_rule');
	$new5->setName('IDV');
	$new5->setValue('IDV_CALL', 'IDV_CALL');
	$new5->setCompanyId(1);
	$new5->setLoanTypeId(22);
	$new5->save();

	//get tokens for a company
	$tokens = $manager->getTokensByCompanyId(1);	
	echo "get by company:\n";
	foreach($tokens as $name => $token)
	{
		echo "\nToken: " . $name;
		echo "\nValue: " . $token->getValue() . "\n";
	}
	//get tokens for a loan type
	echo "\nget by loan type:\n";
	$tokens = $manager->getTokensByLoanTypeId(1,22);	
	foreach($tokens as $name => $token)
	{
		echo "\nToken: " . $name;
		echo "\nValue: " . $token->getValue() . "\n";
	}	
	//get tokens by application and/or company loan type
	echo "\nget by Application:\n";
	$tokens = $manager->getTokensByApplicationId(119701,'nsc','standard');	
	foreach($tokens as $name => $token)
	{
		echo "\nToken: " . $name;
		echo "\nValue: " . $token->getValue() . "\n";
	}	
	
	//deleting tokens	
	$new->delete();
	$new2->delete();
	$new3->delete();
	$new4->delete();
	$new5->delete();	
}


?>