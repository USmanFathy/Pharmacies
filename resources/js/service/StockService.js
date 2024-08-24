import ExceptionHandling from './ExceptionHandling.js'
import { useStore, ActionTypes } from "../store";
import instance from './index';

export default class StockService {


	getStocks(keyword,storeID,start) {
		//SHOW LOADING
		const store = useStore();
		store.dispatch(ActionTypes.PROGRESS_BAR, true);
		const api = '/api/stock_list';
		const formData = new FormData();
		formData.append('keyword', keyword);
		formData.append('storeID', storeID);
		formData.append('start', start);
		return instance()(
			{
				method: 'post',
				url: api,
				data: formData,
			}
		).then(res => res.data)
		.catch((e) => ExceptionHandling.HandleErrors(e))
		.finally(() => {
			store.dispatch(ActionTypes.PROGRESS_BAR, false);
		})
	}


	getItems() {
		//SHOW LOADING
		const store = useStore();
		store.dispatch(ActionTypes.PROGRESS_BAR, true);
		const api = '/api/get_import_stock_list';
		return instance()(
			{
				method: 'get',
				url: api
			}
		).then(res => res.data)
		.catch((e) => ExceptionHandling.HandleErrors(e))
		.finally(() => {
			store.dispatch(ActionTypes.PROGRESS_BAR, false);
		})
	}

	save(postObj) {
		//SHOW LOADING
		const store = useStore();
		store.dispatch(ActionTypes.PROGRESS_BAR, true);
		const api = '/api/save_csv_data';
		const formData = new FormData();
		formData.append('item_list', JSON.stringify(postObj));
		return instance()(
			{
				method: 'post',
				url: api,
				data: formData,
			}
		).then(res => res.data)
			.catch((e) => ExceptionHandling.HandleErrors(e))
			.finally(() => {
				store.dispatch(ActionTypes.PROGRESS_BAR, false);
			})
	}

	
    exportSampleStock()
    {
        const store = useStore();
		store.dispatch(ActionTypes.PROGRESS_BAR, true);
		const api = '/api/export_sample_stock';
		const formData = new FormData();
		return instance()(
			{
				method: 'post',
				url: api,
				data: formData,
			}
		).then(res => res.data)
			.catch((e) => ExceptionHandling.HandleErrors(e))
			.finally(() => {
				store.dispatch(ActionTypes.PROGRESS_BAR, false);
			})
    }

	uploadCSVFile(image) {
		//SHOW LOADING
		const store = useStore();
		store.dispatch(ActionTypes.PROGRESS_BAR, true);
		const api = '/api/import_sample_stock';
		const formData = new FormData();
		formData.append('image', image);

		return instance()(
			{
				method: 'post',
				url: api,
				data: formData,
			}
		).then(res => res.data)
			.catch((e) => ExceptionHandling.HandleErrors(e))
			.finally(() => {
				store.dispatch(ActionTypes.PROGRESS_BAR, false);
			})
	}

	updateItem(postObj,stateObj) {
		//SHOW LOADING
		const store = useStore();
		store.dispatch(ActionTypes.PROGRESS_BAR, true);
		const api = '/api/update_stock';
		const formData = new FormData();
		formData.append('id', postObj.id);
		formData.append('product_name', stateObj.productName);
		formData.append('generic', stateObj.generic);
		formData.append('barcode', postObj.barcode);
		formData.append('type', stateObj.productType);
		formData.append('description', postObj.description);
		formData.append('brand', stateObj.brand);
		formData.append('brand_sector', stateObj.brandSector);
		formData.append('category', stateObj.category);
		formData.append('side_effects', postObj.sideEffects);
		formData.append('pack_size', stateObj.packSize);
		formData.append('strip_size', stateObj.stripSize);
		formData.append('expiry_date', stateObj.expiryDate);
		formData.append('qty', stateObj.unitQty);
		formData.append('sale_price', postObj.packSelling);
		formData.append('purchase_price', stateObj.packPurchase);
		formData.append('mrp', stateObj.mRP);
		formData.append('batch_no', stateObj.batchNo);
		formData.append('tax_1', postObj.tax_1);
		formData.append('tax_2', postObj.tax_2);
		formData.append('tax_3', postObj.tax_3);
		formData.append('discount_percentage', stateObj.disc);
		formData.append('min_stock', stateObj.minStock);
		formData.append('item_location', stateObj.storeLocation);
	
		return instance()(
			{
				method: 'post',
				url: api,
				data: formData,
			}
		).then(res => res.data)
			.catch((e) => ExceptionHandling.HandleErrors(e))
			.finally(() => {
				store.dispatch(ActionTypes.PROGRESS_BAR, false);
			})
	}

	getItem(data) {
		//SHOW LOADING
		const store = useStore();
		store.dispatch(ActionTypes.PROGRESS_BAR, true);
		const api = '/api/get_stock/' + data.id;
		return instance().get(api)
			.then(res => res.data)
			.catch((e) => ExceptionHandling.HandleErrors(e))
			.finally(() => {
				store.dispatch(ActionTypes.PROGRESS_BAR, false);
			})
	}

}