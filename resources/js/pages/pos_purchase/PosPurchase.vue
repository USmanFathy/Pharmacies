<template>
 
    <section>
       <div style="height: 90vh;">
          <div style="height: 0.5vh; background-color:#ccc;">
            <ProgressBar
            v-if="progressBar"
            mode="indeterminate"
            style="height: 0.2em"
          />
          </div>
          <Toolbar style="height: 7.5vh">
          <template #start>
            <h5 class="p-mt-2">
              <b>
                <i style="font-size: 20px" class="pi pi-globe"></i>
                {{ storeName }}
              </b>
            </h5>
          </template>
          <template #end>
            <div class="p-mx-1">
              <Dropdown
                style="width: 15rem"
                v-model="item.type"
                :options="modeList"
                optionLabel="label"
                optionValue="value"
                @change="clearFreeUnit()"
              />
            </div>

            <div class="p-mx-1">
              <Button
                icon="pi pi-plus"
                label="New Profile"
                class="p-button-success"
                @click="openProfileDialog"
              />
            </div>
            <div class="p-mx-1">
              <Button
                icon="pi pi-times"
                label="Clear Screen"
                class="p-button-danger"
                @click="clearAll"
              />
            </div>
          </template>
        </Toolbar>
        <div class="p-col-12" style="height: 8vh;">
           <div class="p-grid">
              <div class="p-col-9 p-pr-0">
                <div class="p-fluid">
                  <AutoComplete
                    :delay="1000"
                    :minLength="3"
                    @item-select="saveItem($event)"
                    scrollHeight="500px"
                    v-model="itemScanBox"
                    :suggestions="itemList"
                    placeholder=" SCAN BARCODE OR SEARCH ITEMS"
                    @complete="searchItem($event)"
                    :dropdown="false"
                    autoFocus
                  >
                    <template #item="slotProps">
                      <div>
                        <span class="p-mr-1">
                          NAME :
                          <b class="pull-right">
                            {{ slotProps.item.product_name.toUpperCase() }}
                          </b>
                        </span>
                        <span class="p-mx-1">
                          EXPIRY DATE :
                          <b class="pull-right">
                            {{ formatExpiryDate(slotProps.item.expiry_date) }}
                          </b>
                        </span>
                      </div>
                      <div>
                        <span>
                          GENERIC :
                          <b class="pull-right">
                            {{ slotProps.item.generic.toUpperCase() }}
                          </b>
                        </span>
                      </div>
                      <div>
                        <small>
                          BATCH NO :
                          <b class="pull-right">
                            {{ slotProps.item.batch_no }}
                          </b>
                        </small>
                        <small>
                          Total Units :
                          <b class="pull-right">
                            {{ slotProps.item.qty }}
                          </b>
                        </small>
                        <small>
                          MRP :
                          <b class="pull-right">
                           {{currency}} {{ slotProps.item.mrp }}
                          </b>
                        </small>
                        <small>
                           Pack Price :
                          <b class="pull-right">
                            {{currency}} {{ slotProps.item.sale_price }}
                          </b>
                        </small>
                        <small>
                          Purchase Price :
                          <b class="pull-right">
                           {{currency}} {{ slotProps.item.purchase_price }}
                          </b>
                        </small>
                        <small>
                          Brand Name :
                          <b class="pull-right">
                            {{ slotProps.item.bName }}
                          </b>
                        </small>
                      </div>
                    </template>
                  </AutoComplete>
                </div>
              </div>
              <div class="p-col-3">
                <div class="p-fluid">
                  <AutoComplete
                    :delay="1000"
                    :minLength="3"
                    @item-select="saveProfile($event)"
                    scrollHeight="500px"
                    v-model="v$.selectedProfile.$model"
                    :suggestions="profilerList"
                    placeholder="Search Profile"
                    @complete="searchProfiler($event)"
                    :dropdown="false"
                    autoFocus
                  >
                    <template #item="slotProps">
                      <div>
                        TITLE :
                        <b class="pull-right">
                          {{ slotProps.item.account_title.toUpperCase() }}
                        </b>
                      </div>
                      <div>
                        Email :
                        <span class="pull-right">
                          {{ slotProps.item.email_address }}
                        </span>
                      </div>
                      <div>
                        Contact :
                        <span class="pull-right">
                          {{ slotProps.item.contact_no }}
                        </span>
                      </div>
                      <div>
                        Account Type :
                        <span class="pull-right">
                          {{ slotProps.item.account_type }}
                        </span>
                      </div>
                    </template>
                  </AutoComplete>
                  <span v-if="v$.selectedProfile.$error && submitted">
                    <span
                      id="p-error"
                      v-for="(error, index) of v$.selectedProfile.$errors"
                      :key="index"
                    >
                      <small class="p-error">{{ error.$message }}</small>
                    </span>
                  </span>
                  <small
                    v-else-if="
                      (v$.selectedProfile.$invalid && submitted) ||
                      v$.selectedProfile.$pending.$response
                    "
                    class="p-error"
                    >{{
                      v$.selectedProfile.required.$message.replace(
                        "Value",
                        "Profile"
                      )
                    }}</small
                  >
                </div>
              </div>
          </div>
        </div>
        <div class="p-col-12 pos-table" style="height: 64vh; background-color: #f9f9f9">
          <table class="table table-stiped table-bordered p-m-0">
              <thead>
                <tr class="pos-heading">
                  <th style="width: 8rem">Batch No</th>
                  <th>Unit Qty</th>
                  <th>Free Qty</th>
                  <th>Sup Bonus</th>
                  <th>Purchase Price ({{currency}})</th>
                  <th>P.Disc (%)</th>
                  <th>After Disc</th>
                  <th>MRP PRICE ({{currency}})</th>
                  <th>Cus Disc %</th>
                  <th>Expiry Date</th>
                  <th v-if="taxNames[0].show">{{taxNames[0].taxName}} %</th>
                  <th v-if="taxNames[1].show">{{taxNames[1].taxName}} %</th>
                  <th v-if="taxNames[2].show">{{taxNames[2].taxName}} %</th>
                  <th>Subtotal ({{currency}})</th>
                  <th>REMOVE</th>
                </tr>
              </thead>
              <tbody>
                <template
                  v-for="savedItem in savedItemList.slice().reverse()"
                  :key="savedItem"
                >
                  <tr class="table-row">
                    <td>
                      <div class="p-inputgroup">
                        <InputText
                          style="height: 30px"
                          v-model="savedItem.batchNo"
                          class="p-p-1"
                        />
                      </div>
                    </td>
                    <td>
                      <div class="p-inputgroup">
                        <InputNumber
                          :useGrouping="false"
                          style="width: 2rem; height: 30px"
                          :min="1"
                          v-model="savedItem.unit"
                          class="p-p-0"
                        />
                      </div>
                    </td>
                    <td>
                      <div class="p-inputgroup">
                        <InputNumber
                          :useGrouping="false"
                          style="width: 2rem; height: 30px"
                          :min="1"
                          v-model="savedItem.freeUnit"
                          class="p-p-0"
                        />
                      </div>
                    </td>
                    <td>
                      <div class="p-inputgroup">
                        <InputNumber
                          :useGrouping="false"
                          style="width: 2rem; height: 30px"
                          :min="1"
                          v-model="savedItem.supplierBonus"
                          class="p-p-0"
                        />
                      </div>
                    </td>
                    <td>
                      <div class="p-inputgroup">
                        <InputNumber
                          style="width: 2rem; height: 30px"
                          :min="1"
                          :minFractionDigits="2"
                          :maxFractionDigits="2"
                          v-model="savedItem.purchasePrice"
                          class="p-p-0"
                        />
                      </div>
                    </td>
                    <td>
                      <div class="p-inputgroup">
                        <InputNumber
                          :useGrouping="false"
                          style="width: 2rem; height: 30px"
                          :minFractionDigits="2"
                          :maxFractionDigits="2"
                          v-model="savedItem.itemDisc"
                          class="p-p-0"
                        />
                      </div>
                    </td>
                    <td>
                      <div class="p-inputgroup">
                        <InputNumber
                          :useGrouping="false"
                          style="width: 2rem; height: 30px"
                          :minFractionDigits="2"
                          :maxFractionDigits="2"
                          v-model="savedItem.purchaseAfterDisc"
                          :disabled="true"
                          class="p-p-0"
                        />
                      </div>
                    </td>
                    <td>
                      <div class="p-inputgroup">
                        <InputNumber
                          style="width: 2rem; height: 30px"
                          :min="1"
                          :minFractionDigits="2"
                          :maxFractionDigits="2"
                          v-model="savedItem.mrp"
                          class="p-p-0"
                        />
                      </div>
                    </td>
                    <td>
                      <div class="p-inputgroup">
                        <InputNumber
                          :useGrouping="false"
                          style="width: 2rem; height: 30px"
                          :minFractionDigits="2"
                          :maxFractionDigits="2"
                          v-model="savedItem.cusDisc"
                          class="p-p-0"
                        />
                      </div>
                    </td>
                    <td>
                      <div class="p-inputgroup">
                         <Calendar
                            id="expiryDate"
                            v-model="savedItem.expiryDate"
                            style="width: 2rem; height: 30px"
                            selectionMode="single"
                            dateFormat="dd-mm-yy"
                            class="p-p-0"
                            
                          />
                      </div>
                    </td>
                    <td v-if="taxNames[0].show">
                      <div class="p-inputgroup">
                        <InputNumber
                          :useGrouping="false"
                          style="width: 2rem; height: 30px"
                          :minFractionDigits="2"
                          :maxFractionDigits="2"
                          v-model="savedItem.tax1"
                          class="p-p-0"
                        />
                      </div>
                    </td>
                    <td v-if="taxNames[1].show">
                      <div class="p-inputgroup">
                        <InputNumber
                          :useGrouping="false"
                          style="width: 2rem; height: 30px"
                          :minFractionDigits="2"
                          :maxFractionDigits="2"
                          v-model="savedItem.tax2"
                          class="p-p-0"
                        />
                      </div>
                    </td>
                    <td v-if="taxNames[2].show">
                      <div class="p-inputgroup">
                        <InputNumber
                          :useGrouping="false"
                          style="width: 2rem; height: 30px"
                          :minFractionDigits="2"
                          :maxFractionDigits="2"
                          v-model="savedItem.tax3"
                          class="p-p-0"
                        />
                      </div>
                    </td>
                    <td class="p-text-center">
                      <b> {{getTheSubtotal(savedItem)}} </b>
                    </td>
                    <td class="p-text-center">
                      <Button
                        icon="pi pi-minus"
                        class="p-button-danger p-p-1"
                        @click="clearListItem(savedItem)"
                      />
                    </td>
                  </tr>
                  <tr class="item-detail-row" v-if="savedItem.productID != 0">
                    <td  :colspan="countTaxesLen">
                      <span class="p-mr-1">
                        PRODUCT NAME:
                        <span style="color: #fff; background-color: #c00">
                          {{ limitString(savedItem.productName) }}
                        </span></span
                      >
                      <span class="p-mr-1">
                        GENERIC:
                        <span style="color: #fff; background-color: #c00">
                          {{ limitString(savedItem.generic) }}
                        </span></span
                      >
                      <span class="p-mx-1">
                        SHEET SIZE : {{ savedItem.sheetSize }}
                      </span>
                      <span class="p-mx-1">
                        PACKSIZE : {{ savedItem.packSize }}
                      </span>
                      <span class="p-mx-1">
                        TOTAL UNITS : {{ fixDigits(savedItem.totalUnit) }}
                      </span>
                      <span class="p-mx-1">
                        PACK PRICE : {{currency}} {{ fixDigits(savedItem.sellingPrice) }}
                      </span>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
        </div>
        <div class="p-col-12" style="height: 10vh; background-color:#eee">
          <div class="p-grid">
            <div class="p-col-9 p-pr-0">
              <div class="p-fluid">
                <InputText placeholder="Receipt Description" v-model="item.description" />
              </div>
            </div>
            <div class="p-col-3">
              <div class="p-fluid">
                <InputText placeholder="Bill No" v-model="item.billNo" />
              </div>
            </div>
           
          </div>
        </div>
       </div>  
        <div style="height: 10vh" class="p-grid p-m-0 p-p-0">
        <Button
          class="p-col p-button-success b-style"
          icon="pi pi-home"
          label="HOME"
          @click="redirectHome()"
        />
        <span class="set-bottom-amt p-col"
          >Total QTY <br />
          # {{ savedItemList.length }}</span
        >
        <span class="set-bottom-amt p-col"
          >Total Gross <br />
          {{currency}} {{ fixDigits(totalGross) }}</span
        >
        <span class="set-bottom-amt p-col"
          >Total Disc <br />
          {{currency}} {{ fixDigits(totalDiscAmount) }}</span
        >
        <span class="set-bottom-amt p-col" v-if="taxNames[0].show"
          >Total {{ taxNames[0].taxName }} <br />
          {{currency}} {{ fixDigits(totalTax1) }}</span
        >
        <span class="set-bottom-amt p-col" v-if="taxNames[1].show"
          >Total {{ taxNames[1].taxName }} <br />
          {{currency}} {{ fixDigits(totalTax2) }}</span
        >
        <span class="set-bottom-amt p-col" v-if="taxNames[2].show"
          >Total {{ taxNames[2].taxName }} <br />
          {{currency}} {{ fixDigits(totalTax3) }}</span
        >
        <span class="set-bottom-amt p-col"
          >Net Total <br />
          {{currency}} {{ fixDigits(netTotal) }}</span
        >
        <Button
          class="p-col p-button-warning b-style"
          icon="pi pi-arrow-right"
          label="NEXT"
          @click="openPaymentMethod(!v$.$invalid)"
          :disabled="item.profileID == 0 || netTotal <= 0"
        />
      </div>
    </section>

      <Dialog
      v-model:visible="receiptDailog"
      :style="{ width: '600px' }"
      header="Search Receipt"
      position="top"
    >
      <div class="confirmation-content">
        <i class="pi pi-search p-mr-3" style="font-size: 2rem" />
        <div class="p-fluid">
          <label for="search_receipt"> Enter Receipt No </label>
          <InputText
            id="search_receipt"
            autoFocus
            v-model="item.searchReceiptNo"
            placeholder="e.g PUR-02502100000000"
          />
        </div>
      </div>
      <template #footer>
        <Button
          label="No"
          icon="pi pi-times"
          class="p-button-text"
          @click="refundReceiptDialog = false"
        />
        <Button
          label="Search"
          icon="pi pi-search"
          class="p-button-success"
          :disabled="item.searchReceiptNo == ''"
          @click="fetchReceiptNo()"
        />
      </template>
    </Dialog>
    
  <PaymentScreen
    :receiptDetail="{
      dialogStatus: paymentDialog,
      itemSource: item.type,
      restriction: 'No',
      dialogTilte: dialogTitle,
      customerID: this.item.profileID,
      customerName: this.state.selectedProfile,
      closeConfirmation: true,
    }"
    v-on:closePaymentScreenEvent="closePaymentScreen"
    v-on:getProceededPaymentsEvent="getProceededPayments"
  />

  <ProfilerDialog
    :profilerDetail="{
      status: this.profileStatus,
      profilerID: 0,
      statusType: this.statusType,
      dialogTitle: this.dialogTitle,
      currentUserID: this.currentUserID,
    }"
    v-on:updateProfilerStatus="updateProfilerStatus"
  />
</template>
<script lang="ts">
import { Options, Vue } from "vue-class-component";
import PosService from "../../service/PosService.js";
import ProfilerService from "../../service/ProfilerService.js";
import ChartService from "../../service/ChartService.js";
import useVuelidate from "@vuelidate/core";
import { required, requiredIf, helpers } from "@vuelidate/validators";
import Toaster from "../../helpers/Toaster";
import moment from "moment";
import AutoComplete from "primevue/autocomplete";
import SearchFilter from "../../components/SearchFilter.vue";
import PreviewAccountingReceipt from "../../components/PreviewAccountingReceipt.vue";
import { ref, defineComponent, toRefs, reactive } from "vue";
import PaymentScreen from "../../components/PaymentScreen.vue";
import { useStore, ActionTypes } from "../../store";
import ProfilerDialog from "../../components/ProfilerDialog.vue";
import { ItemList,CounterEntry,PaymentListType } from "../pos_purchase/IPurchaseReceipt";
import router from "../../router";

@Options({
  title: 'Purchases',
  components: {
    AutoComplete,
    SearchFilter,
    PreviewAccountingReceipt,
    PaymentScreen,
    ProfilerDialog,
  },
})
export default class PosPurchase extends Vue {
  private modeList = [
    { label: "PURCHASE", value: "PUR" },
    { label: "PURCHASE RETURN", value: "RPU" },
  ];

  private paymentDialog = false;
  private profileStatus = false;
  private statusType = "New";
  private storeName = "Loading...";
  private receiptDailog = false;
  private submitted = false;
  private currentUserID = 0;
  private itemScanBox = "";
  private dialogTitle = "";
  private profilerService;
  private posService;
  private toast;
  private storeList = [];
  private profilerList = [];
  private itemList = [];
  private store = useStore();

  private taxNames = [
    {
      taxName: "",
      show: false,
      optionalReq: "",
      taxValue: 0,
      accountHead: "",
      accountID: 0,
    },
    {
      taxName: "",
      show: false,
      optionalReq: "",
      taxValue: 0,
      accountHead: "",
      accountID: 0,
    },
    {
      taxName: "",
      show: false,
      optionalReq: "",
      taxValue: 0,
      accountHead: "",
      accountID: 0,
    },
  ];

  private state = reactive({
    selectedProfile: "",
  });

  private savedItemList: ItemList[] = [];

  private validationRules = {
    selectedProfile: {
      required,
    },
  };

  private counterEntry: CounterEntry [] = [];

  private paymentList: PaymentListType [] = [];

  private item = {
    id: 0,
    profileID: 0,
    discount: 0,
    totalPaid: 0,
    totalTendered: 0,
    totalChange: 0,
    totalGrossAmt: 0,
    totalBill: 0,
    totalTax1: 0,
    totalTax2: 0,
    totalTax3: 0,
    totalTax: 0,
    description: "",
    billNo: "",
    paymentMethod: "",
    searchReceiptNo: "",
    status: "Active",
    type: "PUR",
  };

  private v$ = useVuelidate(this.validationRules, this.state);

  //DEFAULT METHOD OF TYPE SCRIPT
  created() {
    this.profilerService = new ProfilerService();
    this.posService = new PosService();
    this.toast = new Toaster();
  }

  mounted() {
    this.loadList();
  }

  get progressBar() {
    return this.store.getters.getProgressBar;
  }
  
  searchProfiler(event) {
    setTimeout(() => {
      this.profilerService.searchProfiler(event.query.trim()).then((data) => {
        this.profilerList = data.records;
      });
    }, 200);
  }

  searchItem(event) {
    setTimeout(() => {
      this.posService.searchItem(event.query.trim()).then((data) => {
        this.itemList = data.records;
      });
    }, 200);
  }

  saveProfile(event) {
    const profileInfo = event.value;
    this.state.selectedProfile = profileInfo.account_title;
    this.item.profileID = profileInfo.id;
  }

  saveItem(event) {
    const itemInfo = event.value;

    this.savedItemList.push({
      mode: "Pack",
      stockID: itemInfo.id,
      productID: itemInfo.product_id,
      productName: itemInfo.product_name,
      generic: itemInfo.generic,
      itemDescription: itemInfo.description,
      unit: 1,
      totalUnit: 0,
      stockQty: Number(itemInfo.qty),
      freeUnit: 0,
      supplierBonus: 0,
      batchNo: itemInfo.batch_no,
      packSize: Number(itemInfo.pack_size),
      sheetSize: Number(itemInfo.strip_size),
      purchasePrice: Number(itemInfo.purchase_price),
      orginalSPrice: Number(itemInfo.sale_price),
      sellingPrice: Number(itemInfo.sale_price),
      mrp: Number(itemInfo.mrp),
      brandName: itemInfo.bName,
      sectorName: itemInfo.bSector,
      categoryName: itemInfo.cName,
      productType: itemInfo.pType,
      expiryDate: itemInfo.expiry_date,
      cusDisc: Number(itemInfo.discount_percentage),
      purchaseAfterDisc: 0,
      itemDisc: 0,
      tax1: Number(itemInfo.tax_1),
      tax2: Number(itemInfo.tax_2),
      tax3: Number(itemInfo.tax_3),
      subTotal: 0,
    });

    this.itemScanBox = "";
  }

  loadList() {
    this.posService.getItems().then((data) => {
      // //taxNames
      this.taxNames = [];

      this.storeList = data.stores;

      this.taxNames.push({
        taxName: data.storeTaxes[0].tax_name_1,
        show: data.storeTaxes[0].show_1 == "true" ? true : false,
        optionalReq: data.storeTaxes[0].required_optional_1,
        taxValue:
          data.storeTaxes[0].show_1 == "true"
            ? Number(data.storeTaxes[0].tax_value_1)
            : 0,
        accountHead: data.storeTaxes[0].tax_name1.chartName,
        accountID: data.storeTaxes[0].link1,
      });

      this.taxNames.push({
        taxName: data.storeTaxes[0].tax_name_2,
        show: data.storeTaxes[0].show_2 == "true" ? true : false,
        optionalReq: data.storeTaxes[0].required_optional_2,
        taxValue:
          data.storeTaxes[0].show_2 == "true"
            ? Number(data.storeTaxes[0].tax_value_2)
            : 0,
        accountHead: data.storeTaxes[0].tax_name2.chartName,
        accountID: data.storeTaxes[0].link2,
      });

      this.taxNames.push({
        taxName: data.storeTaxes[0].tax_name_3,
        show: data.storeTaxes[0].show_3 == "true" ? true : false,
        optionalReq: data.storeTaxes[0].required_optional_3,
        taxValue:
          data.storeTaxes[0].show_3 == "true"
            ? Number(data.storeTaxes[0].tax_value_3)
            : 0,
        accountHead: data.storeTaxes[0].tax_name3.chartName,
        accountID: data.storeTaxes[0].link3,
      });

      this.currentUserID = data.currentUserID;
      this.storeName = data.storeName;
    });
  }

  getTheSubtotal(data) {
    
    const qty = Number(data.unit);
    const price = Number(data.purchasePrice);
    const discount = Number(data.itemDisc);
    const mrp   = Number(data.mrp);

    const tax1 = data.tax1;
    const tax2 = data.tax2;
    const tax3 = data.tax3;
    const totalTax = tax1 + tax2 + tax3;

    const avgTax = 100 + totalTax;
    const tax = (mrp / avgTax) * totalTax;
		const packPrice = mrp - tax;

    const total = qty * price;
    const disAmount = (total / 100) * discount;

    const afterDis = total - disAmount;
    const afterTax = (afterDis / 100) * (totalTax);

    const netTotal = afterDis + afterTax;

    data.subTotal = Number(this.fixDigits(netTotal));

    //TOTAL UNITS
    data.totalUnit = (data.packSize * data.unit) + data.freeUnit + data.supplierBonus;
    
    //PACK PRICE
    data.sellingPrice = Number(packPrice);

    //AFTER DISCOUNT PURCHASE PRICE
    data.purchaseAfterDisc = Number(this.fixDigits(price-disAmount));

    return this.fixDigits(netTotal);
  }

  get totalGross() {
    let total = 0;
    this.savedItemList.forEach((e) => {
      total = total + e.purchasePrice * e.unit;
    });

    return total;
  }

  get totalTax1() {
    let total = 0;

    this.savedItemList.forEach((e) => {
      const tax = e.tax1;
      const price = e.purchasePrice * e.unit;
      const afterDisc = (price / 100) * e.itemDisc;
      total = total + ((price - afterDisc) / 100) * tax;
    });

    return Number(total.toFixed(2));
  }

  get totalTax2() {
    let total = 0;
    this.savedItemList.forEach((e) => {
      const tax = e.tax2;
      const price = e.purchasePrice * e.unit;
      const afterDisc = (price / 100) * e.itemDisc;
      total = total + ((price - afterDisc) / 100) * tax;
    });

    return Number(total.toFixed(2));
  }

  get totalTax3() {
    let total = 0;
    this.savedItemList.forEach((e) => {
      const tax = e.tax3;
      const price = e.purchasePrice * e.unit;
      const afterDisc = (price / 100) * e.itemDisc;
      total = total + ((price - afterDisc) / 100) * tax;
    });

    return Number(total.toFixed(2));
  }

  get totalDiscAmount() {
    let total = 0;
    this.savedItemList.forEach((e) => {
      const price = e.purchasePrice * e.unit;
      total = total + (price / 100) * e.itemDisc;
    });

    return total;
  }

  get netTotal() {
    return Number(
      (
        this.totalGross -
        this.totalDiscAmount +
        this.totalTax1 +
        this.totalTax2 +
        this.totalTax3
      ).toFixed(2)
    );
  }

  fixDigits(amt) {
    let total = 0;

    if (amt != null) {
      total = amt.toFixed(2);
    }
    return total;
  }

  clearAll() {
    
    this.savedItemList = [];
    this.paymentList = [];

    this.item = {
      id: 0,
      profileID: 0,
      discount: 0,
      totalGrossAmt: 0,
      totalBill: 0,
      totalPaid: 0,
      totalTendered: 0,
      totalChange: 0,
      totalTax1: 0,
      totalTax2: 0,
      totalTax3: 0,
      totalTax: 0,
      description: "",
      billNo: "",
      searchReceiptNo: "",
      paymentMethod: "",
      status: "Active",
      type: this.item.type,
    };

    this.itemScanBox = "";
    this.state.selectedProfile = "";
  }

  formatExpiryDate(d) {
    return moment(d).format("MMM-YYYY");
  }

  clearListItem(item) {
    this.savedItemList.splice(this.savedItemList.indexOf(item), 1);
    this.toast.showSuccess("Item Deleted Successfully");
  }

  limitString(str) {
    if (str.length > 40) str = str.substring(0, 40) + "...";
    return str;
  }

  closePaymentScreen() {
    this.paymentDialog = false;
  }

  getProceededPayments(paymentList) {
    this.paymentList = paymentList;
    const tenderedList = this.getTotalPaid(paymentList);
    this.item.totalPaid = Number(tenderedList[0]);
    this.item.totalTendered = Number(tenderedList[1]);
    this.item.totalChange = Number(tenderedList[2]);

    const method = this.getPaymentMethod(paymentList);
    this.item.paymentMethod = method;

    this.item.discount = this.totalDiscAmount;
    this.item.totalGrossAmt = this.totalGross;
    this.item.totalBill = this.netTotal;
    this.item.totalTax1 = this.totalTax1;
    this.item.totalTax2 = this.totalTax2;
    this.item.totalTax3 = this.totalTax3;
    this.item.totalTax = Number(
      this.totalTax1 + this.totalTax2 + this.totalTax3
    );

    this.setAccountingEntries();

    this.posService
      .savePurchaseItem(this.item, this.paymentList, this.savedItemList, this.counterEntry)
      .then((res) => {
        if (res.alert == "info") {
          this.clearAll();
        }

        this.toast.handleResponse(res);
      });

    this.paymentDialog = false;
    this.submitted = false;
  }

  openPaymentMethod(isFormValid) {
    this.submitted = true;
    if ((isFormValid = true)) {
      this.paymentDialog = true;
      this.store.dispatch(
        ActionTypes.TOTAL_BILL,
        Number(this.fixDigits(this.netTotal))
      );
    }
  }

  get totalPaidCash()
  {
    let total = 0;

    this.paymentList.forEach(e => {
      if(e.paymentType == 'Cash')
      {
        total = total + e.transTotalAmount;
      }
    });

    return total;
  }

  get totalPaidBank()
  {
    let total = 0;

    this.paymentList.forEach(e => {
      if(e.paymentType != 'Cash')
      {
        total = total + e.transTotalAmount;
      }
    });

    return total;
  }

  getTotalPaid(paymnetList) {
    let totalPaid = 0;
    let totalTendered = 0;
    let totalChange = 0;

    paymnetList.forEach((e) => {
      if (e.paymentType != "Tip") {
        totalPaid = totalPaid + Number(e.transTotalAmount);
        totalTendered = totalTendered + Number(e.tendered);
        totalChange = totalChange + Number(e.change);
      }
    });

    return [totalPaid, totalTendered, totalChange];
  }

  get totalBalance()
  {
    return this.netTotal - this.item.totalPaid;
  }

  getPaymentMethod(paymnetList) {
    let method = "";

    if (paymnetList.length == 0) {
      method = "Pay Later";
    } else if (paymnetList.length == 1) {
      method = paymnetList[0].paymentType;
    } else if (paymnetList.length > 1) {
      method = "Split";
    }

    return method;
  }

  clearFreeUnit() {
    this.toast.showSuccess("Mode Changed Successfully");

    if (this.item.type == "RPU") {
      this.receiptDailog = true;
    }

    this.submitted = false;
    this.clearAll();
  }

  fetchReceiptNo() {
    this.posService.getPurchaseItems(this.item.searchReceiptNo).then((data) => {
      if (data.receipt != null) {
        this.state.selectedProfile = data.receipt.profile_name.accountName;
        this.item.profileID = data.receipt.profile_name.id;
        this.item.billNo =
          data.receipt.bill_no == null
            ? ""
            : data.receipt.bill_no;
        this.item.description =
          data.receipt.description == null ? "" : data.receipt.description;
      }

      if (data.receiptItems != null) {
        data.receiptItems.forEach((e) => {
          this.savedItemList.push({
            mode: e.mode,
            stockID: Number(e.stock_id),
            productID: Number(e.product_id),
            productName: e.item_name,
            generic: e.generic_name,
            itemDescription: e.item_description,
            unit: Number(e.unit),
            totalUnit: Number(e.total_unit),
            stockQty: Number(e.stock_detail.qty),
            freeUnit: Number(e.free_unit),
            supplierBonus: Number(e.supplier_bonus),
            batchNo: e.batch_no,
            packSize: Number(e.pack_size),
            sheetSize: Number(e.sheet_size),
            purchasePrice: Number(e.purchase_price),
            orginalSPrice: Number(e.stock_detail.sale_price),
            sellingPrice: Number(e.selling_price),
            mrp: Number(e.mrp),
            brandName: e.brand_name,
            sectorName: e.sector_name,
            categoryName: e.category_name,
            productType: e.product_type,
            expiryDate: e.expiry_date,
            itemDisc: Number(e.purchase_disc),
            purchaseAfterDisc: Number(e.after_disc),
            cusDisc: Number(e.item_disc),
            tax1: Number(e.tax_1),
            tax2: Number(e.tax_2),
            tax3: Number(e.tax_3),
            subTotal: Number(e.sub_total),
          });
        });

        this.receiptDailog = false;
      }
    });
  }

  //OPEN DIALOG TO ADD NEW ITEM
  openProfileDialog() {
    this.dialogTitle = "Add New Profile";
    this.profileStatus = true;
    this.statusType = "New";
  }

  updateProfilerStatus(res) {
    this.profileStatus = false;
    if (res[0] == "load") {
      this.state.selectedProfile = res[1].account_title;
      this.item.profileID = res[1].id;
    }
  }

  redirectHome() {
    router.replace({ path: "/store/dashboard", params: {} });
  }

  get countTaxesLen()
  {
    let ctr = 0;

    if(this.taxNames[0].show)
    {
      ctr++;
    }

    if(this.taxNames[1].show)
    {
      ctr++;
    }

    if(this.taxNames[2].show)
    {
      ctr++;
    }

    return Number(ctr + 12);
  }

  setAccountingEntries() {
   
    this.counterEntry = [];

    if (this.item.type == "PUR") {

      this.counterEntry.push({
          accountID: 3,
          accountHead: 'Inventory',
          amount: this.totalGross-this.totalDiscAmount,
          type: "Debit",
      });

      //ADDING TAXES
      if (this.totalTax1 != 0) {
        this.counterEntry.push({
          accountID: this.taxNames[0].accountID,
          accountHead: this.taxNames[0].accountHead,
          amount: this.totalTax1,
          type: "Debit",
        });
      }

      if (this.totalTax2 != 0) {
        this.counterEntry.push({
          accountID: this.taxNames[1].accountID,
          accountHead: this.taxNames[1].accountHead,
          amount: this.totalTax2,
          type: "Debit",
        });
      }

      if (this.totalTax3 != 0) {
        this.counterEntry.push({
          accountID: this.taxNames[2].accountID,
          accountHead: this.taxNames[2].accountHead,
          amount: this.totalTax3,
          type: "Debit",
        });
      }


      if (this.totalBalance == 0) {

        if (this.totalPaidCash > 0) {
          this.counterEntry.push({
            accountID: 2,
            accountHead: 'Cash in hand',
            amount: this.totalPaidCash,
            type: "Credit",
          });
        }
        
        if (this.totalPaidBank > 0) {
          this.counterEntry.push({
            accountID: 8,
            accountHead: 'Cash at bank',
            amount: this.totalPaidBank,
            type: "Credit",
          });
        }

      } else if (this.totalBalance == this.netTotal) {
        this.counterEntry.push({
          accountID: 5,
          accountHead: "Accounts payable",
          amount: this.netTotal,
          type: "Credit",
        });
      } else if(this.totalBalance > 0 && this.item.totalPaid > 0) {
          if (this.totalPaidCash > 0) {
            this.counterEntry.push({
              accountID: 2,
              accountHead: 'Cash in hand',
              amount: this.totalPaidCash,
              type: "Credit",
            });
          }
          
          if (this.totalPaidBank > 0) {
            this.counterEntry.push({
              accountID: 8,
              accountHead: 'Cash at bank',
              amount: this.totalPaidBank,
              type: "Credit",
            });
          }

          this.counterEntry.push({
            accountID: 5,
            accountHead: "Accounts payable",
            amount: this.totalBalance,
            type: "Credit",
          });
      }
      
    } else if(this.item.type == "RPU") {

      if (this.totalBalance == 0) {

        if (this.totalPaidCash > 0) {
          this.counterEntry.push({
            accountID: 2,
            accountHead: 'Cash in hand',
            amount: this.totalPaidCash,
            type: "Debit",
          });
        }
        
        if (this.totalPaidBank > 0) {
          this.counterEntry.push({
            accountID: 8,
            accountHead: 'Cash at bank',
            amount: this.totalPaidBank,
            type: "Debit",
          });
        }

      } else if (this.totalBalance == this.netTotal) {
        this.counterEntry.push({
          accountID: 4,
          accountHead: "Accounts receivable",
          amount: this.netTotal,
          type: "Debit",
        });
      } else if(this.totalBalance > 0 && this.item.totalPaid > 0) {
          if (this.totalPaidCash > 0) {
            this.counterEntry.push({
              accountID: 2,
              accountHead: 'Cash in hand',
              amount: this.totalPaidCash,
              type: "Debit",
            });
          }
          
          if (this.totalPaidBank > 0) {
            this.counterEntry.push({
              accountID: 8,
              accountHead: 'Cash at bank',
              amount: this.totalPaidBank,
              type: "Debit",
            });
          }

          this.counterEntry.push({
            accountID: 4,
            accountHead: "Accounts receivable",
            amount: this.totalBalance,
            type: "Debit",
          });
      }

        this.counterEntry.push({
          accountID: 3,
          accountHead: 'Inventory',
          amount: this.totalGross-this.totalDiscAmount,
          type: "Credit",
      });

      //ADDING TAXES
      if (this.totalTax1 != 0) {
        this.counterEntry.push({
          accountID: this.taxNames[0].accountID,
          accountHead: this.taxNames[0].accountHead,
          amount: this.totalTax1,
          type: "Credit",
        });
      }

      if (this.totalTax2 != 0) {
        this.counterEntry.push({
          accountID: this.taxNames[1].accountID,
          accountHead: this.taxNames[1].accountHead,
          amount: this.totalTax2,
          type: "Credit",
        });
      }

      if (this.totalTax3 != 0) {
        this.counterEntry.push({
          accountID: this.taxNames[2].accountID,
          accountHead: this.taxNames[2].accountHead,
          amount: this.totalTax3,
          type: "Credit",
        });
      }
    }
    else
    {
      //NOTHING
    }
  }

  get currency() {
    return this.store.getters.getCurrency;
  }
}
</script>

<style scoped>
.b-style {
  border-radius: 0px;
}

.item-detail-row {
  background-color: #0b932a;
  border-bottom: 2px solid #ccc;
  color: #fff;
}

.item-detail-row td {
  font-size: 12px;
  padding: 1px;
}

.apply-style {
  margin-top: 2px;
  padding: 5px;
  width: 100%;
  border: none;
}

.pos-heading {
  background-color: #11467e;
  color: #fff;
}

.pos-heading th {
  padding: 0;
  padding-left: 3px;
  text-transform: uppercase;
}

.remove-item {
  color: #c00;
}

.remove-item:hover {
  vertical-align: center;
  cursor: pointer;
}

.table-row td {
  padding: 3px;
}

.pos-table {
  height: 83vh;
  max-height: 83vh;
  overflow-y: scroll;
  background-color: #fff;
}

.set-bottom-amt {
  text-align: center;
  text-transform: uppercase;
  font-size: 14px;
  background-color: #11467e;
  border-right: 1px dotted #ccc;
  color: #fff;
}
</style>