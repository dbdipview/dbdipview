-- redact personal data in some tables

update "HR members"."HR employees"
	set "Employee Name" = 'REDACTED_FROM_DDV';
