<template>
  <section>
    <div class="app-container">
      <Toolbar>
        <template #start>
          <Breadcrumb
            :home="home"
            :model="items"
            class="p-menuitem-text p-p-1"
          />
        </template>

        <template #end>
          <div class="p-mx-2">
            <Button
              icon="pi pi-search"
              class="p-button-primary px-4"
              @click="openDialog"
            />
          </div>
          <div class="">
           <Button
                icon="pi pi-file-excel"
                class="p-button-success px-4"
                @click="dt.exportCSV()"
            />
          </div>
        </template>
      </Toolbar>
      <div class="m-2 mt-4 mb-4 p-text-center">
          <h5>{{searchFilters.type}} Report</h5>
          <p>{{resultTitle}}</p>
      </div>
      <div class="p-mt-2">
        <DataTable
          ref="dt"
          :value="lists"
          :lazy="true"
          :scrollable="true"
          class="p-datatable-sm p-datatable-striped p-datatable-gridlines"
        >
          <template #empty>
            <div class="p-text-center p-p-3">No records found</div>
          </template>
          <Column header="Store Name" style="width: 170rem">
            <template #body="slotProps">
              {{ slotProps.data.branchName }} ({{ slotProps.data.branchCode }})
            </template>
          </Column>
          <Column header="Date" >
            <template #body="slotProps">
              {{ formatDate(slotProps.data.receiptDate) }}
            </template>
          </Column>
          <Column field="receiptNo" header="Receipt No"></Column>
          <Column header="Customer Name" style="width: 170rem">
            <template #body="slotProps">
              {{ slotProps.data.customerName }} ({{ slotProps.data.customerContact }})
            </template>
          </Column>
          <Column  header="User Name">
            <template #body="slotProps">
              {{ slotProps.data.userName }} ({{ slotProps.data.userContact }})
            </template>
          </Column>
          <Column  header="Item Name">
            <template #body="slotProps">
              {{ slotProps.data.itemName }}
            </template>
          </Column>
          <Column  header="Generic Name">
            <template #body="slotProps">
              {{ slotProps.data.genericName }}
            </template>
          </Column>
          <Column  header="Description">
            <template #body="slotProps">
              {{ slotProps.data.itemDescription }}
            </template>
          </Column>
          <Column  header="Qty">
            <template #body="slotProps">
              {{ slotProps.data.unit }}x
            </template>
          </Column>
          <Column  header="Total Units">
            <template #body="slotProps">
              {{ slotProps.data.totalUnit }}x
            </template>
          </Column>
          <Column header="Batch No">
             <template #body="slotProps">
              {{ slotProps.data.batchNo }}
            </template>
          </Column>
          <Column header="Pack Size">
             <template #body="slotProps">
              {{ slotProps.data.packSize }}
            </template>
          </Column>
          <Column header="Sheet Size">
             <template #body="slotProps">
              {{ slotProps.data.sheetSize }}
            </template>
          </Column>
          <Column :header="'Selling Price ('+currency+')'">
             <template #body="slotProps">
              {{ formatAmount(slotProps.data.sellingPrice) }}
            </template>
          </Column>
          <Column :header="'Mrp ('+currency+')'">
             <template #body="slotProps">
              {{ formatAmount(slotProps.data.mrp) }}
            </template>
          </Column>
          <Column header="Brand">
             <template #body="slotProps">
              {{ slotProps.data.brandName }}
            </template>
          </Column>
          <Column header="Sector Name">
             <template #body="slotProps">
             {{ slotProps.data.sectorName }}
            </template>
          </Column><Column header="Category">
             <template #body="slotProps">
             {{ slotProps.data.categoryName }}
            </template>
          </Column><Column header="Product Type">
             <template #body="slotProps">
             {{ slotProps.data.productType }}
            </template>
          </Column>
          <Column header="Expiry">
             <template #body="slotProps">
              {{ formatDate(slotProps.data.expiryDate) }}
            </template>
          </Column>
          <Column header="Item Disc">
             <template #body="slotProps">
              {{ formatAmount(slotProps.data.itemDisc) }} %
            </template>
          </Column>
          <Column  :header="taxNames[0].taxName + '(%)'" v-if="taxNames[0].show == 'true'">
             <template #body="slotProps">
               {{ formatAmount(slotProps.data.tax1) }} %
            </template>
          </Column>
          <Column  :header="taxNames[1].taxName + '(%)'" v-if="taxNames[1].show == 'true'">
             <template #body="slotProps">
              {{ formatAmount(slotProps.data.tax2) }} %
            </template>
          </Column>
          <Column  :header="taxNames[2].taxName + '(%)'" v-if="taxNames[2].show == 'true'">
             <template #body="slotProps">
              {{ formatAmount(slotProps.data.tax3) }} %
            </template>
          </Column>
          <Column :header="'Subtotal ('+currency+')'">
             <template #body="slotProps">
              {{ formatAmount(slotProps.data.subTotal) }}
            </template>
          </Column>
        </DataTable>
      </div>

      <Dialog
        v-model:visible="productDialog"
        :style="{ width: '50vw' }"
        :maximizable="true"
        position="top"
        class="p-fluid"
      >
        <template #header>
          <h5 class="p-dialog-titlebar p-dialog-titlebar-icon">
           <i class="pi pi-search" style="font-size:1.2rem;"></i> {{ dialogTitle }}
          </h5>
        </template>
        <div class="p-field">
            <label for="filterStore">Filter Date</label>
            <Dropdown
              v-model="searchFilters.filterType"
              :options="filterDates"
              :filter="true"
              optionLabel="key"
              optionValue="value"
            />
        </div>
        <h5>OR</h5>
        <div class="p-grid">
          <div class="p-col">
            <div class="p-field">
              <label for="from">Date From</label>
              <input type="date" id="from"  v-model="searchFilters.date1" class="form-control">
            </div>
          </div>
          <div class="p-col">
            <div class="p-field">
              <label for="to">Date To</label>
              <input type="date" id="to"  v-model="searchFilters.date2" class="form-control">
            </div>
          </div>
        </div>
        <div class="p-grid">
          <div class="p-col">
              <div class="p-field">
                <label for="brandName">Brands</label>
                <Dropdown
                    id="brandName"
                    v-model="searchFilters.brandName"
                    :options="brands"
                    :filter="true"
                    optionLabel="option_name"
                    optionValue="option_name"
                />
            </div>
          </div>
           <div class="p-col">
              <div class="p-field">
                <label for="sectorName">Brand Sectors</label>
                <Dropdown
                    id="sectorName"
                    v-model="searchFilters.sectorName"
                    :options="sectors"
                    :filter="true"
                    optionLabel="option_name"
                    optionValue="option_name"
                />
            </div>
          </div>
          <div class="p-col">
            <div class="p-field">
                <label for="categoryName">Category</label>
                <Dropdown
                    id="categoryName"
                    v-model="searchFilters.categoryName"
                    :options="categories"
                    :filter="true"
                    optionLabel="option_name"
                    optionValue="option_name"
                />
            </div>
          </div>
          <div class="p-col">
              <div class="p-field">
                <label for="productType">Product Type</label>
                <Dropdown
                    id="productType"
                    v-model="searchFilters.productType"
                    :options="productTypes"
                    :filter="true"
                    optionLabel="option_name"
                    optionValue="option_name"
                />
             </div>
          </div>
        </div>
        
        
        <div class="p-field">
            <label for="filterStore">Batch No</label>
            <InputText  v-model="searchFilters.batchNo" />
        </div>
        <div class="p-grid">
          <div class="p-col">
            <div class="p-field">
              <label for="type">Report</label>
              <Dropdown
                  id="type"
                  v-model="searchFilters.type"
                  :options="reportTypes"
                  :filter="true"
                  optionLabel="name"
                  optionValue="name"
              />
            </div>
          </div>
          <div class="p-col">
             <div class="p-field">
                <label for="filterStore">Branch</label>
                <Dropdown
                    v-model="searchFilters.storeID"
                    :options="filterBranch"
                    :filter="true"
                    optionLabel="name"
                    optionValue="id"
                />
              </div>
          </div>
        </div>
        <div class="p-grid">
          <div class="p-col">
            <div class="p-field">
              <label for="type">Customer</label>
                <AutoComplete
                  :delay="1000"
                  :minLength="3"
                  @item-select="saveProfile($event)"
                  scrollHeight="500px"
                  v-model="searchFilters.customerName"
                  :suggestions="profilerList"
                  placeholder="Search Profile"
                  @complete="searchProfiler($event)"
                  :dropdown="false"
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
            </div>
          </div>
          <div class="p-col">
             <div class="p-field">
                <label for="filterStore">User</label>
                <AutoComplete
                  :delay="1000"
                  :minLength="3"
                  @item-select="saveUser($event)"
                  scrollHeight="500px"
                  v-model="searchFilters.UserName"
                  :suggestions="userList"
                  placeholder="Search User"
                  @complete="searchUser($event)"
                  :dropdown="false"
                >
                  <template #item="slotProps">
                    <div>
                      NAME :
                      <b class="pull-right">
                        {{ slotProps.item.name.toUpperCase() }}
                      </b>
                    </div>
                    <div>
                      Email :
                      <span class="pull-right">
                        {{ slotProps.item.email }}
                      </span>
                    </div>
                    <div>
                      Contact :
                      <span class="pull-right">
                        {{ slotProps.item.contact }}
                      </span>
                    </div>
                  </template>
                </AutoComplete>
              </div>
          </div>
        </div>
        <template #footer>
          <Button
            type="submit"
            label="Search"
            icon="pi pi-search"
            class="p-button-primary"
            @click="loadList"
          />
        </template>
      </Dialog>
    </div>
  </section>
</template>
<script lang="ts">
import { ref } from "vue";
import { Options, mixins } from "vue-class-component";
import StoreReports from "../../service/StoreReports";
import UtilityOptions from "../../mixins/UtilityOptions";
import ProfilerService from "../../service/ProfilerService.js";
import UserService from "../../service/UserService.js";
import AutoComplete from "primevue/autocomplete";

@Options({
  title: 'Sales/Refund Report',
  components: {AutoComplete},
})

export default class SalesRefundReport extends mixins(UtilityOptions) {
  private dt = ref();
  private lists  = [];
  private profilerList = [];
  private userList = [];
  private reportService;
  private profilerService;
  private userService;
  private resultTitle = "";
  private productDialog = false;
  private loading = false;
  private home = { icon: "pi pi-home", to: "/" };
  private items = [
    { label: "Reports", to: "reports" },
    { label: "Sales/Refund Report", to: "sales-refund-reports"  },
  ];

  private searchFilters = {
    id: "",
    date1: "",
    date2: "",
    filterType: "None",
    storeID: 0,
    type: 'Sales',
    customerID: 0,
    userID: 0,
    brandName: 'All',
    sectorName: 'All',
    categoryName: 'All',
    productType: 'All',
    batchNo: '',
    customerName: 'All',
    UserName: 'All',
  };

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

  private reportTypes = [
    {
      name: 'Sales',
    },
    {
      name: 'Refund',
    },
  ];

  private brands:any = [];
  private sectors:any = [];
  private categories:any = [];
  private productTypes:any = [];

  private filterDates = [];
  private dialogTitle;
  private submitted = false;
  private filterBranch = [];

  //CALLING WHENEVER COMPONENT LOADS
  created() {
    this.reportService = new StoreReports();
    this.profilerService = new ProfilerService();
    this.userService = new UserService();
  }
  
   //CALLNING AFTER CONSTRUCTOR GET CALLED
  mounted() {
    this.storeList();
    this.loadList();
  }

  //OPEN DIALOG TO ADD NEW ITEM
  openDialog() {       
    this.submitted = false;
    this.dialogTitle = "Filter Report";
    this.productDialog = true;
  }

  storeList()
  {
    this.reportService.getFilterList().then((res) => {
      this.filterBranch  = res.stores;
      this.filterDates   = res.datesList;
      this.brands        = res.brands;
      this.sectors       = res.brandSector;
      this.categories    = res.categories;
      this.productTypes  = res.productTypes;

      const defaultFilter = {id:0,option_name:'All'};
      this.brands.push(defaultFilter);
      this.sectors.push(defaultFilter);
      this.categories.push(defaultFilter);
      this.productTypes.push(defaultFilter);

      this.taxNames = [];

        this.taxNames.push({
          taxName: res.storeTaxes[0].tax_name_1,
          show: res.storeTaxes[0].show_1,
          optionalReq: res.storeTaxes[0].required_optional_1,
          taxValue:
            res.storeTaxes[0].show_1 == "true"
              ? Number(res.storeTaxes[0].tax_value_1)
              : 0,
          accountHead: res.storeTaxes[0].tax_name1.chartName,
          accountID: res.storeTaxes[0].link1,
        });

        this.taxNames.push({
          taxName: res.storeTaxes[0].tax_name_2,
          show: res.storeTaxes[0].show_2,
          optionalReq: res.storeTaxes[0].required_optional_2,
          taxValue:
            res.storeTaxes[0].show_2 == "true"
              ? Number(res.storeTaxes[0].tax_value_2)
              : 0,
          accountHead: res.storeTaxes[0].tax_name2.chartName,
          accountID: res.storeTaxes[0].link2,
        });

        this.taxNames.push({
          taxName: res.storeTaxes[0].tax_name_3,
          show: res.storeTaxes[0].show_3,
          optionalReq: res.storeTaxes[0].required_optional_3,
          taxValue:
            res.storeTaxes[0].show_3 == "true"
              ? Number(res.storeTaxes[0].tax_value_3)
              : 0,
          accountHead: res.storeTaxes[0].tax_name3.chartName,
          accountID: res.storeTaxes[0].link3,
        });
    });
  }
 
  // USED TO GET SEARCHED ASSOCIATE
  loadList() {
    this.loading = true;
    this.reportService.salesRefundReport(this.searchFilters).then((res) => {
        const data = this.camelizeKeys(res);
        this.resultTitle = data.resultTitle;
        this.lists = data.record;
        this.loading = false;
      });
    this.productDialog = false;
  }

  searchProfiler(event) {
    setTimeout(() => {
      this.profilerService.searchProfiler(event.query.trim()).then((data) => {
        this.profilerList = data.records;
      });
    }, 200);
  }
  
  searchUser(event) {
    setTimeout(() => {
      this.userService.searchUser(event.query.trim()).then((data) => {
        this.userList = data.records;
      });
    }, 200);
  }

  saveProfile(event) {
    const profileInfo = event.value;
    this.searchFilters.customerName = profileInfo.account_title;
    this.searchFilters.customerID = profileInfo.id;
  }
  
  saveUser(event) {
    const userInfo = event.value;
    this.searchFilters.UserName = userInfo.name;
    this.searchFilters.userID = userInfo.id;
  }

  get currency() {
    return this.store.getters.getCurrency;
  }
}
</script>