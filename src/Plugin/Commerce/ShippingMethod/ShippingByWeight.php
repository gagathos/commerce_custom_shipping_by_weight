<?php

namespace Drupal\commerce_custom_shipping_by_weight\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\PackageTypeManagerInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodBase;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_shipping\ShippingService;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\physical\Weight;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the ShippingByWeight shipping method.
 *
 * @CommerceShippingMethod(
 *   id = "commerce_custom_shipping_by_weight",
 *   label = @Translation("Shipping by shipment weight"),
 * )
 */
class ShippingByWeight extends ShippingMethodBase {

  protected $stateService;
  public $rulesOrder;

  /**
   * Constructs a new ShippingByWeight object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_shipping\PackageTypeManagerInterface $package_type_manager
   *   The package type manager.
   * @param \Drupal\Core\State\StateInterface $stateService
   *   The Drupal State Service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PackageTypeManagerInterface $package_type_manager, StateInterface $stateService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $package_type_manager);
    $this->services['default'] = new ShippingService('default', $this->configuration['rate_label']);
    $this->stateService = $stateService;
    $this->rulesOrder = ['weight', 'unit', 'operator', 'price', 'currency'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.commerce_package_type'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'rate_label' => NULL,
      'base_rate_amount' => NULL,
      'services' => ['default'],
      'weight_calculation_rules' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $amount = $this->configuration['base_rate_amount'];
    // A bug in the plugin_select form element causes $amount to be incomplete.
    if (isset($amount) && !isset($amount['number'], $amount['currency_code'])) {
      $amount = NULL;
    }

    $form['rate_label'] = [
      '#type' => 'textfield',
      '#title' => t('Shipment type label'),
      '#description' => t('Shown to customers during checkout.'),
      '#default_value' => $this->configuration['rate_label'],
      '#required' => TRUE,
    ];

    $form['base_rate_amount'] = [
      '#type' => 'commerce_price',
      '#title' => t('Base shipping rate amount'),
      '#default_value' => $amount,
      '#required' => TRUE,
    ];
    $form['weight_calculation_rules'] = [
      '#type' => 'textarea',
      '#title' => t('Weight Calculation Rules'),
      '#default_value' => $this->unpackRules(isset($this->configuration['weight_calculation_rules']) ? $this->configuration['weight_calculation_rules'] : []),
      '#required' => TRUE,
      '#description' => t('Build your weight calculation rules here. <br> It should look something like:<br> <pre>weight, unit, operator, price, currency' . "\n" . '1,lb,<,5.00,USD</pre><br> for each row.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['rate_label'] = $values['rate_label'];
      $this->configuration['base_rate_amount'] = $values['base_rate_amount'];
      $this->configuration['weight_calculation_rules'] = $this->packRules($values['weight_calculation_rules']);
    }
  }

  /**
   *
   */
  public function packRules(String $rules_text) {
    $packed = [];
    $rules = explode("\n", trim($rules_text));
    foreach ($rules as $rule_text) {
      $packed[] = explode(',', $rule_text);
    }
    return $packed;
  }

  /**
   *
   */
  public function unpackRules(array $rules_array) {
    $output = '';
    $rules = [];
    foreach ($rules_array as $rule) {
      $rules[] = implode(',', $rule);
    }
    $output = implode("\n", $rules);
    return $output;
  }

  /**
   * Calculates shipment rate depending on weight conditions.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   Shipment interface entity.
   *
   * @return array
   *   Returns calculated rates.
   */
  public function calculateRates(ShipmentInterface $shipment) {
    // Rate IDs aren't used in a flat rate scenario because there's always a
    // single rate per plugin, and there's no support for purchasing rates.
    $weight = $shipment->getWeight();
    if (!$weight) {
      // Need a weight to use this shipping. This should never happen but let's protect from a bad config.
      return FALSE;
    }
    $rate_id = 0;
    $amount = $this->configuration['base_rate_amount'];

    foreach ($this->configuration['weight_calculation_rules'] as $rule) {
      if (!isset($rule[4])) {
        // Default USD.
        $rule[4] = 'USD';
      }
      $rule = array_combine($this->rulesOrder, $rule);

      /** @var \Drupal\physical\Weight $weight */
      $weight = $weight->convert($rule['unit']);
      $condition_weight = new Weight(trim($rule['weight']), trim($rule['unit']));

      // Return evaluation results.
      switch (trim($rule['operator'])) {
        case '>=':
          $matches = $weight->greaterThanOrEqual($condition_weight);
          break;

        case '>':
          $matches = $weight->greaterThan($condition_weight);
          break;

        case '<=':
          $matches = $weight->lessThanOrEqual($condition_weight);
          break;

        case '<':
          $matches = $weight->lessThan($condition_weight);
          break;

        case '==':
          $matches = $weight->equals($condition_weight);
          break;

        default:
          throw new \InvalidArgumentException("Invalid operator {$rule['operator']}");
      }
      if ($matches) {
        $matching_rule = $rule;
        // This means that the first rule to evaluate true will be returned.
        break;
        // There may be a way to automatically re-order rules into a logical order to make the interface more flexible, but that might be too ambiguous.
      }

    }
    if (isset($matching_rule)) {
      // Calculate shipping amount.
      $shipping_value = trim($matching_rule['price']);
      // Set shipping amount.
      $amount = new Price((string) $shipping_value, trim($matching_rule['currency']));
      $rates = [];
      // Set shipping rate.
      $rates[] = new ShippingRate($rate_id, $this->services['default'], $amount);

      return $rates;
    }
  }

}
