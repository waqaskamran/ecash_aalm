# NADA table indices

alter table nada_vehicle_description add index (vic_make);
alter table nada_vehicle_description add index (vic_year);
alter table nada_vehicle_description add index (vic_series);
alter table nada_vehicle_description add index (vic_body);

alter table nada_vehicle_value add index (vic_make);
alter table nada_vehicle_value add index (vic_year);
alter table nada_vehicle_value add index (vic_series);
alter table nada_vehicle_value add index (vic_body);
alter table nada_vehicle_value add index (region);

alter table nada_vehicle_vin add index (vin_prefix);
alter table nada_vehicle_vin add index (vic_make);
alter table nada_vehicle_vin add index (vic_year);
alter table nada_vehicle_vin add index (vic_series);
alter table nada_vehicle_vin add index (vic_body);